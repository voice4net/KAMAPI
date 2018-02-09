<?php

/*
URL:			  :  https://uchkam.epbx.com/kamapi/user_agents.php
Header            :  Accept: application/json 
Post Data Format  :  [{"user_agent":"SIPAgent"}]
Put Data Format   :  {"id":24,"user_agent":"SIPAgent"} 
Delete Data Format:  [{"id":14}]
*/


require_once("SimpleRest.php");
require_once("db.php");

class UserAgentsHandler extends SimpleRest
{
    function get_records()
    {
        /*
        1. This function can handle valid Get all requests
        2. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        */
        $db = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rawData = array(
                'error' => 'No databases found!'
            );
        } else {
            $statusCode = 200;
        }
        $sql = "SELECT id, key_name AS user_agent FROM user_agents;";
        $result = $conn->query($sql);
        $rows = array();
        while ($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
        $requestContentType = $_SERVER['HTTP_ACCEPT'];
        $this->setHttpHeaders($requestContentType, $statusCode);
        if (strpos($requestContentType, 'text/html') !== false) {
            $response = $this->encodeHtml($rows);
            echo $response;
        } else if ((strpos($requestContentType, 'application/xml') !== false) || (strpos($requestContentType, 'text/xml') !== false)) {
            $this->setHttpHeaders('application/xml', $statusCode);
            $response = $this->encodeXml($rows);
            echo $response;
        } else {
            #This case is either request type is json or empty
            $this->setHttpHeaders("application/json", $statusCode);
            $response = json_encode($rows);
            echo $response;
        }
        $conn->close();
    }

    function encodeHtml($responseData)
    {
        $htmlResponse = "<table border='1'>";
        foreach ($responseData as $key => $value) {
            $htmlResponse .= "<tr><td>" . $key . "</td><td>" . $value["user_agent"] . "</td></tr>";
        }
        $htmlResponse .= "</table>";
        return $htmlResponse;
    }

    function encodeXml($responseData)
    {
        $records = new SimpleXMLElement('<?xml version="1.0"?><records></records>');
        foreach ($responseData as $key => $value) {
            $record = $records->addChild("record");
            $record->addChild("id", $value["id"]);
            $record->addChild("user_agent", $value["user_agent"]);
        }
        return $records->asXML();
    }

    function get_record($id)
    {
        /*
        1. This function can handle valid Get all requests
        2. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        3. This can handle invalid id  or empty
        4. This function can handle SQL Injection
        */
        $rows = array();
        $db = new DB();
        if ($id == Null) {
            $statusCode = 400;
            $rows = array(
                'error' => 'Invalid Request Format',
                'SampleURLFormat' => '{"URL": "https://uchkam.epbx.com/kamapi/user_agnets.php?id=10","headers" : "HTTP_ACCEPT: text/html or application/json or application/xml"}'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        }
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rows = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("SELECT id, key_name AS user_agent FROM user_agents where id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 404;
            $rows = array(
                array(
                    'error' => 'No matching user_agent found with the given id'
                )
            );
            $response = json_encode($rows);
            echo $response;
            return;
        }
        $requestContentType = $_SERVER['HTTP_ACCEPT'];
        $this->setHttpHeaders($requestContentType, $statusCode);
        if (strpos($requestContentType, 'text/html') !== false) {
            $response = $this->encodeHtml($rows);
            echo $response;
        } else if ((strpos($requestContentType, 'application/xml') !== false) || (strpos($requestContentType, 'text/xml') !== false)) {
            $response = $this->encodeXml($rows);
            echo $response;
        } else {
            #This case is either request type is json or invalid HTTP_ACCEPT
            $this->setHttpHeaders("application/json", $statusCode);
            $response = json_encode($rows[0]);
            echo $response;
        }
    }

    function insert_record($post_data)
    {
        /*
        1. This function can handle valid insertion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. User_agent duplication
        */
        if (isset($post_data["user_agent"])) {
            $user_agent = $post_data["user_agent"];
            if ($user_agent == "" or $user_agent == " ") {
                $this->invalid_data_format('[{"user_agent":"SIPAgent"}]');
                return;
            }
        } else {
            $this->invalid_data_format('[{"user_agent":"SIPAgent"}]');
            return;
        }
        $db = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("call insert_user_agent(?)");
        $stmt->bind_param('s', $user_agent);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        $rows = array();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 409;
            $rows = array(
                array(
                    'error' => "user_agent(" . $user_agent . ") already exists in table or table doesn't exist"
                )
            );
        }
        $this->setHttpHeaders("application/json", $statusCode);
        $response = json_encode($rows[0]);
        echo $response;
    }

    function invalid_data_format($data_format)
    {
        $statusCode = 400;
        $this->setHttpHeaders("application/json", $statusCode);
        $rows = array(
            'error' => 'Invalid Data Format',
            'SampleJsonDataFormat' => $data_format
        );
        $response = json_encode($rows);
        echo $response;
        return;
    }

    function insert_records($post_data_items)
    {
        /*
        1. This function can handle valid insertion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. User_agent duplication
        */
        $number_of_rows_inserted = 0;
        foreach ($post_data_items as $post_data) {

            if (isset($post_data["user_agent"])) {
                $user_agent = $post_data["user_agent"];
                if ($user_agent == "" or $user_agent == " ") {
                    continue;
                }
            } else {
                $this->invalid_data_format('[{"user_agent":"SIPAgent1"}, {"user_agent":"SIPAgent2"}]');
                return;
            }
            $db = new DB();
            $conn = $db->connect();
            if (empty($conn)) {
                $statusCode = 404;
                $this->setHttpHeaders("application/json", $statusCode);
                $rows = array(
                    'error' => 'No databases found!'
                );
                $response = json_encode($rows);
                echo $response;
                return;
            }
            $stmt = $conn->prepare("call insert_user_agent(?)");
            $stmt->bind_param('s', $user_agent);
            $stmt->execute();
            $result = $db->get_result($stmt);
            $conn->close();
            $rows = array();
            while ($r = array_shift($result)) {
                $rows[] = $r;
            }
            if ($rows != Null) {
                $number_of_rows_inserted = $number_of_rows_inserted + 1;
            }

        }

        if ($number_of_rows_inserted != 0) {
            $statusCode = 200;
            $this->setHttpHeaders("application/json", $statusCode);
            $response = json_encode(array('inserted_rows_count' => $number_of_rows_inserted));
            echo $response;
        } else {
            $statusCode = 400;
            $this->setHttpHeaders("application/json", $statusCode);
            $response = json_encode(array('inserted_rows_count' => 0, 'error' => 'Table or stored procedure may not exist'));
            echo $response;
        }
        return;
    }

    function update_record($put_data)
    {
        /*
        1. This function can handle valid updation(update only User_agent)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. User_agent duplication
        5. If User_agent is valid and id is invalid then function doesn't update or insert data(you have to use post)
        */
        $rows = array();
        if ((isset($put_data["id"])) and (isset($put_data["user_agent"]))) {
            $id = $put_data["id"];
            $user_agent = $put_data["user_agent"];
            if ($user_agent == "" or $user_agent == " " or $id <= 0) {
                $this->invalid_data_format('{"id":24,"user_agent":"SIPAgent"}');
                return;
            }
        } else {
            $this->invalid_data_format('{"id":24,"user_agent":"SIPAgent"}');
            return;
        }
        $db = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("call update_user_agent(?,?)");
        $stmt->bind_param('is', $id, $user_agent);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 409;
            $rows = array(array(
                'error' => 'user_agent(' . $user_agent . ') already exists in table or No such id exists in the table'
            ));
        }
        $this->setHttpHeaders("application/json", $statusCode);
        $response = json_encode($rows [0]);
        echo $response;
    }

    function delete_record($_DELETE)
    {
        /*
        1. This function can handle valid deletion(there should be a matching record in database with id and User_agent)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. User_agent duplication
        */
        $delete_data = json_decode($_DELETE["0"], true);
        if (isset($delete_data["id"])) {
            $id = $delete_data["id"];
            if ($id <= 0) {
                $this->invalid_data_format(array(
                    'error' => 'Invalid Data Format',
                    'SampleJsonDataFormat' => '[{"id":24}]'
                ));
                return;
            }
        } else {
            $statusCode = 400;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows = array(
                'error' => 'Invalid Data Format Gopi',
                'SampleJsonDataFormat' => '{"id":24}'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        }
        $db = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rawData = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rawData);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("Delete from user_agents where id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $conn->close();

        $this->setHttpHeaders("application/json", $statusCode);
    }

    function delete_records($_DELETE)
    {
        $delete_ids_arr = array();
        $place_holder_string = "";
        $paramtype = "";
        foreach ($_DELETE as $data) {
            $delete_data = json_decode($data, true);
            if (isset($delete_data["id"])) {
                if ($delete_data["id"] <= 0) {
                    continue;
                }
                array_push($delete_ids_arr, $delete_data["id"]);
                $place_holder_string = $place_holder_string . "?,";
                $paramtype .= "i";

            } else {
                continue;
            }
        }

        $db = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rawData = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rawData);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }

        $place_holder_string = substr($place_holder_string, 0, -1);
        $sql_statement = "Delete from user_agents where id IN ($place_holder_string)";
        $a_params[] = &$paramtype;
        $stmt = $conn->prepare($sql_statement);
        for ($i = 0; $i < count($delete_ids_arr); $i++) {
            $a_params[] = &$delete_ids_arr[$i];
        }

        call_user_func_array(array($stmt, 'bind_param'), $a_params);

        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();

        $this->setHttpHeaders("application/json", 200);

    }
}

/*
controls the RESTful services
URL mapping
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $view = "";
    if (isset($_GET["id"]))
        $view = $_GET["id"];
    if ($view == "") {
        $user_agent_handler = new UserAgentsHandler();
        $user_agent_handler->get_records();
    } else {
        $user_agent_handler = new UserAgentsHandler();
        $user_agent_handler->get_record($_GET["id"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_agent_handler = new UserAgentsHandler();
    $count = count($data);
    #This can handle regular inserts [{}]
    if ($count == 1)
        $user_agent_handler->insert_record($data[0]);
    #This can handle regular inserts [{},{},..]
    else
        $user_agent_handler->insert_records($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_agent_handler = new UserAgentsHandler();
    $user_agent_handler->update_record($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $count = count($_GET);
    $user_agent_handler = new UserAgentsHandler();
    if ($count == 1)
        $user_agent_handler->delete_record($_GET);
    else
        $user_agent_handler->delete_records($_GET);
}
?>
