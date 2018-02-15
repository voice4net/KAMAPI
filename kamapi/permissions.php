<?php

/*
URL: https://uchkam.epbx.com/kamapi/addresses.php
Header            :  Accept: application/json 
Post Data Format  : [{"ip_addr":"127.0.0.1", "description":"localhost"}]
Put Data Format   : {"id":21,grp:1,"ip_addr":"123.123.123.12","mask":32,"port":0,"tag":"sample tag","description":"example"} 
Delete Data Format: {"id":21}
*/

require_once("SimpleRest.php");
require_once("db.php");


class AddressesHandler extends SimpleRest
{
    function get_records()
    {
        /*
        This function can handle
        1. all valid Get requests
        2. It would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
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
        $sql = "SELECT id, grp, ip_addr, mask, port, tag, description FROM address;";
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
            $htmlResponse .= "<tr><td>" . $key . "</td><td>" . $value["grp"] . "</td><td>" . $value["ip_addr"] . "</td><td>" . $value["mask"] . "</td><td>" . $value["port"] . "</td><td>" . $value["tag"] . "</td></tr>";
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
            $record->addChild("grp", $value["grp"]);
            $record->addChild("ip_addr", $value["ip_addr"]);
            $record->addChild("mask", $value["mask"]);
            $record->addChild("port", $value["port"]);
            $record->addChild("tag", $value["tag"]);
        }
        return $records->asXML();
    }

    function get_record($id)
    {
        /*
        This function can handle
        1. All valid Get requests
        2. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        3. Invalid id  or empty
        4. SQL Injection
        */
        $rows = array();
        $db = new DB();
        if ($id == Null) {
            $statusCode = 400;
            $rows = array(
                'error' => 'Invalid Request Format',
                'SampleURLFormat' => '{"URL": "https://uchkam.epbx.com/kamapi/addresses.php?id=10","headers" : "HTTP_ACCEPT: text/html or application/json or application/xml"}'
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
        $stmt = $conn->prepare("SELECT id, grp, ip_addr, mask, port, tag, description FROM address WHERE id=?");
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
                    'error' => 'No matching address found with the given id'
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
        */
        if (isset($post_data["ip_addr"])) {
            $ip_addr = $post_data["ip_addr"];
            $description = $post_data["description"];
            if ($ip_addr == "" or $ip_addr == " ") {
                $this->invalid_data_format('[{"ip_addr":"127.0.0.1", "description":"localhost"}]');
                return;
            }
        } else {
            $this->invalid_data_format('[{"ip_addr":"127.0.0.1", "description":"localhost"}]');
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
        $stmt = $conn->prepare("call insert_address(?,?)");
        $stmt->bind_param('ss', $ip_addr,$description);
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
                    'error' => 'ip_addr(' . $ip_addr . ') already exists in table'
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
        4. ip_addr duplication
        */
        $number_of_rows_inserted = 0;
        foreach ($post_data_items as $post_data) {

            if (isset($post_data["ip_addr"])) {
                $ip_addr = $post_data["ip_addr"];
                $description = $post_data["description"];
                if ($ip_addr == "" or $ip_addr == " ") {
                    continue;
                }
            } else {
                $this->invalid_data_format('[{"ip_addr":"127.0.0.1", "description":"localhost"},{"ip_addr":"127.0.0.1", "description":"localhost"}]');
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
            $stmt = $conn->prepare("call insert_address(?,?)");
            $stmt->bind_param('ss', $ip_addr,$description);
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
        1. This function can handle valid updation
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. If ip_addr is valid and id is invalid then function doesn't update or insert data(you have to use post)
        */
        $rows = array();
        if ((isset($put_data["id"])) and ((isset($put_data["ip_addr"])) or (isset($put_data["grp"])) or (isset($put_data["mask"])) or (isset($put_data["port"])) or (isset($put_data["tag"])) or (isset($put_data["description"])))) {
            $id = $put_data["id"];
            $ip_addr = $put_data["ip_addr"];
            $grp = $put_data["grp"];
            $mask = $put_data["mask"];
            $port = $put_data["port"];
            $tag = $put_data["tag"];
            $description = $put_data["description"];

            if ((($ip_addr == "" or $ip_addr == " ") and (!is_null($ip_addr))) or $id <= 0) {
                $this->invalid_data_format('{"id":21,grp:1,"ip_addr":"123.123.123.12","mask":32,"port":0,"tag":"sample tag","description":"example"}');
                return;
            }
        } else {
            $this->invalid_data_format('{"id":21,grp:1,"ip_addr":"123.123.123.12","mask":32,"port":0,"tag":"sample tag","description":"example"}');
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
        $stmt = $conn->prepare("call update_address(?,?,?,?,?,?,?)");
        $stmt->bind_param('iisiiss', $id, $grp, $ip_addr, $mask, $port, $tag, $description);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 409;
            $rows = array(array(
                'error' => 'Address(' . $ip_addr . ') already exists in table or No such id exists in the table'
            ));
        }
        $this->setHttpHeaders("application/json", $statusCode);
        $response = json_encode($rows[0]);
        echo $response;
    }

    function delete_record($_DELETE)
    {
        /*
        1. This function can handle valid deletion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        */
        $rows = array();
        $delete_data = json_decode($_DELETE["0"], true);
        if (isset($delete_data["id"])) {
            $id = $delete_data["id"];
            if ($id <= 0) {
                $this->invalid_data_format(array(
                        'error' => 'Invalid Data Format',
                        'SampleJsonDataFormat' => '[{"id":24}]'
                    )
                );
                return;
            }
        } else {
            $this->invalid_data_format(array(
                    'error' => 'Invalid Data Format',
                    'SampleJsonDataFormat' => '[{"id":24}]'
                )
            );
            return;

        }
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
        $stmt = $conn->prepare("Delete from address where id=?");
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
        } else {
            $statusCode = 200;
        }

        $place_holder_string = substr($place_holder_string, 0, -1);
        $sql_statement = "Delete from address where id IN ($place_holder_string)";
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
        $addresses_handler = new AddressesHandler();
        $addresses_handler->get_records();
    } else {
        $addresses_handler = new AddressesHandler();
        $addresses_handler->get_record($_GET["id"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $addresses_handler = new AddressesHandler();
    $count = count($data);
    #This can handle regular inserts [{}]
    if ($count == 1)
        $addresses_handler->insert_record($data[0]);
    else
        $addresses_handler->insert_records($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    ## This code works with first data format 
    $data = json_decode(file_get_contents('php://input'), true);
    $addresses_handler = new AddressesHandler();
    $addresses_handler->update_record($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $count = count($_GET);
    $addresses_handler = new AddressesHandler();
    if ($count == 1)
        $addresses_handler->delete_record($_GET);
    else
        $addresses_handler->delete_records($_GET);
}
?>
