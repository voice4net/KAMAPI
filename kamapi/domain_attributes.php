<?php

/*
URL: https://uchkam.epbx.com/kamapi/domain_attributes.php
Header            :  Accept : application/json 
Post Data Format  : [{"did":"123.123.123.12","name":"default_destination_host","type":3,"value":"5040"}]
Put Data Format   : {"id":9,"did":"123.123.123.12","name":"default_destination_host","type":3,"value":"5040"} 
Delete Data Format: {"id":9}
*/

require_once("SimpleRest.php");
require_once("db.php");

class DomainAttributesHandler extends SimpleRest
{
    function get_records()
    {
        /*
        1. This function can handle valid Get all domain attributes request
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
        $sql = "SELECT id, did,name, type, value, last_modified FROM domain_attrs;";
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
            $htmlResponse .= "<tr><td>" . $key . "</td><td>" . $value["did"] . "</td><td>" . $value["name"] . "</td><td>" . $value["type"] . "</td><td>" . $value["value"] . "</td><td>" . $value["last_modified"] . "</td></tr>";
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
            $record->addChild("did", $value["did"]);
            $record->addChild("name", $value["name"]);
            $record->addChild("type", $value["type"]);
            $record->addChild("value", $value["value"]);
            $record->addChild("last_modified", $value["last_modified"]);
        }
        return $records->asXML();
    }

    function get_record($_GET_DATA)
    {
        /*
        This function can handle
        1. Valid Get record request
        2. It can send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        3. Invalid id  or empty
        4. SQL Injection
        */
        $rows = array();
        $db = new DB();
        $dids_arr = array();
        $place_holder_string = "";
        $paramtype = "";

        foreach ($_GET_DATA as $data) {
            $get_data = json_decode($data, true);
            if ($get_data['did'] == '' or $get_data['did'] == ' ') {
                continue;
            }
            array_push($dids_arr, $get_data['did']);
            $place_holder_string = $place_holder_string . "?,";
            $paramtype .= "s";
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

        $place_holder_string = substr($place_holder_string, 0, -1);
        $sql_statement = "SELECT id, did,name, type, value, last_modified FROM domain_attrs WHERE did IN ($place_holder_string)";
        $a_params[] = &$paramtype;
        $stmt = $conn->prepare($sql_statement);
        for ($i = 0; $i < count($dids_arr); $i++) {
            $a_params[] = &$dids_arr[$i];
        }

        call_user_func_array(array($stmt, 'bind_param'), $a_params);

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
                    'error' => 'No matching domain attribute found with the given did'
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
            $response = json_encode($rows);
            echo $response;
        }
    }

    function insert_record($post_data)
    {
        /*
        This function can handle
        1. valid insertion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. domain attribute duplication
        */
        if (isset($post_data["did"]) and isset($post_data["name"]) and isset($post_data["type"]) and isset($post_data["value"])) {
            $did = $post_data["did"];
            $name = $post_data["name"];
            $type = $post_data["type"];
            $value = $post_data["value"];
            $did = $post_data["did"];
            if ($did == "" or $did == " " or $name == "" or $name == " " or $type == "" or $type == " " or $value == "" or $value == " ") {
                $this->invalid_data_format('[{"did":"127.0.0.1","name":"default_destination_host","type":2,"value":"127.0.0.1"}]');
                return;
            }
        } else {
            $this->invalid_data_format('[{"did":"127.0.0.1","name":"default_destination_host","type":2,"value":"127.0.0.1"}]');
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
        $stmt = $conn->prepare("call insert_domain_attribute(?,?,?,?)");
        $stmt->bind_param('ssis', $did, $name, $type, $value);
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
                    'error' => 'Domain attributes (' . $did . ', ' . $name . ', ' . $type . ', ' . $value . ') already exists in table'
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

            if (isset($post_data["did"]) and isset($post_data["name"]) and isset($post_data["type"]) and isset($post_data["value"])) {
                $did = $post_data["did"];
                $name = $post_data["name"];
                $type = $post_data["type"];
                $value = $post_data["value"];
                $did = $post_data["did"];
                if ($did == "" or $did == " " or $name == "" or $name == " " or $type == "" or $type == " " or $value == "" or $value == " ") {
                    continue;
                }
            } else {
                $this->invalid_data_format('[{"did":"127.0.0.1","name":"default_destination_host","type":2,"value":"127.0.0.1"},{"did":"127.0.0.1","name":"default_destination_host","type":2,"value":"127.0.0.1"}]');
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
            $stmt = $conn->prepare("call insert_domain_attribute(?,?,?,?)");
            $stmt->bind_param('ssis', $did, $name, $type, $value);
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
            $response = json_encode(array('inserted_rows_count' => 0, 'error possibilities' => 'Table or stored procedure may not exist or Data already exists in the table'));
            echo $response;
        }
        return;
    }

    function update_record($put_data)
    {
        /*
        This function can handle 
        1. valid updation
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. domain attribute duplication
        5. If domain attribute is valid and id is invalid then function doesn't update or insert data(you have to use post)
        6. if user wants to set did to null or if user doesn't want to update did
        */
        $rows = array();
        if (isset($put_data["id"]) and isset($put_data["did"]) and isset($put_data["name"]) and isset($put_data["type"]) and isset($put_data["value"])) {
            $id = $put_data["id"];
            $did = $put_data["did"];
            $name = $put_data["name"];
            $type = $put_data["type"];
            $value = $put_data["value"];
            if ($id <= 0 or $did == "" or $did == " " or $name == "" or $name == " " or $type == "" or $type == " " or $value == "" or $value == " ") {
                $this->invalid_data_format('{"id":5,"did":"127.0.0.1","name":"default_destination_host","type":2,"value":"127.0.0.1"}');
                return;
            }
        } else {
            $this->invalid_data_format('{"did":"127.0.0.1","name":"default_destination_host","type":2,"value":"127.0.0.1"}');
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
        $stmt = $conn->prepare("call update_domain_attribute(?,?,?,?,?)");
        $stmt->bind_param('issis', $id, $did, $name, $type, $value);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 409;
            $rows = array(
                'error' => 'Domain attribute(' . $did . ',' . $name . ',' . $type . ',' . $value . ') already exists in table'
            );
        }
        $this->setHttpHeaders("application/json", $statusCode);
        $response = json_encode($rows);
        echo $response;
    }

    function delete_record($_DELETE)
    {
        /*
        1. This function can handle valid deletion(there should be a matching record in database with id)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        */
        $delete_data = json_decode($_DELETE["0"], true);
        if (isset($delete_data["id"])) {
            $id = $delete_data["id"];

            if ($id <= 0) {
                $this->invalid_data_format(array(
                    'error' => 'Invalid Data Format',
                    'SampleJsonDataFormat' => '{"id":24}'
                ));
                return;
            }
        } else {
            $this->invalid_data_format(array(
                'error' => 'Invalid Data Format',
                'SampleJsonDataFormat' => '{"id":24}'
            ));
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
        $stmt = $conn->prepare("Delete from domain_attrs where id=?");
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
        $sql_statement = "Delete from domain_attrs where id IN ($place_holder_string)";
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
    /* $param = "";
	$data =  "";
    if ( isset( $_GET[ "id" ] ) )
	{
		$param = "id";
		$data = $_GET[ "id" ];
	}
	elseif(isset( $_GET[ "did" ] ))
	{
		$param = "did";
		$data = $_GET[ "did" ];
	}
		
    if ( $param == "" ) {
        $domain_attributes_handler = new DomainAttributesHandler();
        $domain_attributes_handler->get_records();
    } else {
        $domain_attributes_handler = new DomainAttributesHandler();
        $domain_attributes_handler->get_record($param,$data);
    } */

    $count = count($_GET);
    $domain_attributes_handler = new DomainAttributesHandler();

    if ($count == 0)
        $domain_attributes_handler->get_records();
    else
        $domain_attributes_handler->get_record($_GET);


} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $count = count($data);
    $domain_attributes_handler = new DomainAttributesHandler();
    if ($count == 1)
        $domain_attributes_handler->insert_record($data[0]);
    else
        $domain_attributes_handler->insert_records($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    ## This code works with first data format 
    $data = json_decode(file_get_contents('php://input'), true);
    $domain_attributes_handler = new DomainAttributesHandler();
    $domain_attributes_handler->update_record($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $count = count($_GET);
    $domain_attributes_handler = new DomainAttributesHandler();
    if ($count == 1)
        $domain_attributes_handler->delete_record($_GET);
    else
        $domain_attributes_handler->delete_records($_GET);
}
?>
