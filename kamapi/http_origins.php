<?php
require_once("SimpleRest.php");
require_once("db.php");
class HttpOriginsHandler extends SimpleRest
{
    function encodeXml($responseData)
    {
        $records = new SimpleXMLElement('<?xml version="1.0"?><records></records>');
        foreach ($responseData as $key => $value) {
            $record = $records->addChild("record");
            $record->addChild("id", $value["id"]);
            $record->addChild("http_origin", $value["http_origin"]);
            $record->addChild("active", $value["active"]);
            $record->addChild("modified", $value["modified"]);
        }
        return $records->asXML();
    }
    function encodeHtml($responseData)
    {
        $htmlResponse = "<table border='1'>";
        foreach ($responseData as $key => $value) {
            $htmlResponse .= "<tr><td>" . $key . "</td><td>" . $value["http_origin"] . "</td><td>" . $value["active"] . "</td><td>" . $value["modified"] . "</td></tr>";
        }
        $htmlResponse .= "</table>";
        return $htmlResponse;
    }
    
	function invalid_data_format( $data_format )
    {
        $statusCode = 400;
        $this->setHttpHeaders( "application/json", $statusCode );
        $rows     = array(
             'error' => 'Invalid Data Format',
            'SampleJsonDataFormat' => $data_format 
        );
        $response = json_encode( $rows );
        echo $response;
        return;
    }
	
	function get_records()
    {
        /*
        1. This function can handle valid Get all requests
        2. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        */
        $db   = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rawData    = array(
                'error' => 'No databases found!'
            );
        } else {
            $statusCode = 200;
        }
        $sql    = "SELECT id, http_origin, active, modified FROM http_origins";
        $result = $conn->query($sql);
        $rows   = array();
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
    function get_record($id)
    {
        /*
        1. This function can handle valid Get all requests
        2. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        3. This can handle invalid id  or empty 
        4. This function can handle SQL Injection
        */
        $rows = array();
        $db   = new DB();
        if ($id == Null) {
            $statusCode = 400;
            $rows       = array(
                'error' => 'Invalid Request Format',
                'SampleURLFormat' => '{"URL": "https://uchkam.epbx.com/kamapi/http_origins.php?id=10","headers" : "HTTP_ACCEPT: text/html or application/json or application/xml"}'
            );
            $response   = json_encode($rows);
            echo $response;
            return;
        }
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rows       = array(
                'error' => 'No databases found!'
            );
            $response   = json_encode($rows);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("SELECT id, http_origin, active, modified FROM http_origins where id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 404;
            $rows       = array(
                array(
                    'error' => 'No matching http_origin record found with the given id'
                )
            );
            $response   = json_encode($rows);
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
            #This case is either request type is json or empty
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
        4. Http_origin duplication
        */
        if (isset($post_data["http_origin"])) {
            $http_origin = $post_data["http_origin"];
			
			if ( $http_origin == "" or $http_origin == " " ) {
				$statusCode = 400;
                $this->invalid_data_format( array(
					'error' => 'Invalid Data Format',
					'SampleJsonDataFormat' => '[{"http_origin":"https://www.example.com"}]'
				) );
                return;
            }
        } else {
            $statusCode = 400;
                $this->invalid_data_format( array(
					'error' => 'Invalid Data Format',
					'SampleJsonDataFormat' => '[{"http_origin":"https://www.example.com"}]'
				) );
			return;
        }
        $db   = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows     = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("call insert_http_origin(?)");
        $stmt->bind_param('s', $http_origin);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        $rows = array();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 409;
            $rows       = array(
                array(
                    'error' => 'Http_origin(' . $http_origin . ') already exists in table'
                )
            );
        }
        $this->setHttpHeaders("application/json", $statusCode);
        $response = json_encode($rows[0]);
        echo $response;
    }
	
	function insert_records( $post_data_items )
    {
        /*
        1. This function can handle valid insertion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. http_origin duplication
        */
		$number_of_rows_inserted = 0;		
		foreach($post_data_items as $post_data){
		
			if ( isset( $post_data[ "http_origin" ] ) ) {
				$http_origin = $post_data[ "http_origin" ];
				if ( $http_origin == "" or $http_origin == " " ) {
					continue;
				}
			} else {				
				$this->invalid_data_format( '[{"http_origin":"https://www.example1.com"}, {"http_origin":"https://www.example2.com"}]' );
				return;
			}
			$db   = new DB();
			$conn = $db->connect();
			if ( empty( $conn ) ) {
				$statusCode = 404;
				$this->setHttpHeaders( "application/json", $statusCode );
				$rows     = array(
					 'error' => 'No databases found!' 
				);
				$response = json_encode( $rows );
				echo $response;
				return;
			} 
			$stmt = $conn->prepare( "call insert_http_origin(?)" );
			$stmt->bind_param( 's', $http_origin );
			$stmt->execute();
			$result = $db->get_result( $stmt );
			$conn->close();
			$rows = array( );
			while ( $r = array_shift( $result ) ) {
				$rows[ ] = $r;
			}
			if ( $rows != Null ) {
				$number_of_rows_inserted = $number_of_rows_inserted + 1;
			}
			
		}
		
		if ($number_of_rows_inserted != 0){
			$statusCode = 200;
			$this->setHttpHeaders( "application/json", $statusCode );
			$response = json_encode( array('inserted_rows_count' => $number_of_rows_inserted));
			echo $response;
		}
		else
		{
			$statusCode = 400;
			$this->setHttpHeaders( "application/json", $statusCode );
			$response = json_encode( array('inserted_rows_count' => 0, 'error' => 'Table or stored procedure may not exist'));
			echo $response;
		}
		return;
    }
	
    function update_record($put_data)
    {
        /*
        1. This function can handle valid updation(update only http_origin)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. Http_origin duplication
        5. If http_origin is valid and id is invalid then function doesn't update or insert data(you have to use post)
        */
        $rows = array();
        if ((isset($put_data["id"])) and ((isset($put_data["http_origin"])) or (isset($put_data["active"])))) {
            $id          = $put_data["id"];
            $http_origin = $put_data["http_origin"];
            if ($http_origin == "")
                $http_origin = Null;
            $active_value = $put_data["active"];
        } else {
            $statusCode = 400;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows     = array(
                'error' => 'Invalid Data Format',
                'SampleJsonDataFormat' => '{"id":24,"http_origin":"https://www.example.com","active":1}'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        }
        $db   = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows     = array(
                'error' => 'No databases found!'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("call update_http_origin(?,?,?)");
        $stmt->bind_param('isi', $id, $http_origin, $active_value);
        $stmt->execute();
        $result = $db->get_result($stmt);
        $conn->close();
        while ($r = array_shift($result)) {
            $rows[] = $r;
        }
        if ($rows == Null) {
            $statusCode = 409;
            $rows       = array(array(
                'error' => 'Http_origin(' . $http_origin . ') already exists in table or No such id exists in the table'
            ));
        }
        $this->setHttpHeaders("application/json", $statusCode);
        $response = json_encode($rows[0]);
        echo $response;
    }
    function delete_record($_DELETE)
    {
        /*
        1. This function can handle valid deletion(there should be a matching record in database with id and http_origin)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. Http_origin duplication
        */
        $rows = array();
		$delete_data = json_decode($_DELETE["0"],true);
        if (isset($delete_data["id"])) {
            $id = $delete_data["id"];
			
			if ( $id <= 0 ) {
				$statusCode = 400;
				$this->setHttpHeaders("application/json", $statusCode);
				$rows     = array(
					'error' => 'Invalid Data Format',
					'SampleJsonDataFormat' => '[{"id":24}]'
				);
				$response = json_encode($rows);
				echo $response;
				return;                
            }
        }
		else {
            $statusCode = 400;
            $this->setHttpHeaders("application/json", $statusCode);
            $rows     = array(
                'error' => 'Invalid Data Format',
                'SampleJsonDataFormat' => '[{"id":24}]'
            );
            $response = json_encode($rows);
            echo $response;
            return;
        }
        $db   = new DB();
        $conn = $db->connect();
        if (empty($conn)) {
            $statusCode = 404;
            $rawData    = array(
                'error' => 'No databases found!'
            );
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare("Delete from http_origins where id=?");
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
		foreach( $_DELETE as $data){
			$delete_data = json_decode($data,true);
			if ( isset( $delete_data[ "id" ] ) ) {								
				if ( $delete_data[ "id" ] <= 0 ) {
					continue;
				}
				array_push($delete_ids_arr, $delete_data[ "id" ]);
				$place_holder_string = $place_holder_string."?," ;
				$paramtype .="i";
				
			}else{
				continue;
			} 			
		}
		
		$db   = new DB();
        $conn = $db->connect();
        if ( empty( $conn ) ) {
            $statusCode = 404;
            $rawData    = array(
                 'error' => 'No databases found!' 
            );
        } else {
            $statusCode = 200;
        }
        
		$place_holder_string = substr($place_holder_string, 0, -1);
		$sql_statement = "Delete from http_origins where id IN ($place_holder_string)";
		$a_params[] = & $paramtype;
		$stmt = $conn->prepare( $sql_statement );
		for($i = 0; $i < count($delete_ids_arr); $i++) {	
			$a_params[] = & $delete_ids_arr[$i];
		}
		
		call_user_func_array(array($stmt, 'bind_param'), $a_params);

        $stmt->execute();
        $result = $db->get_result( $stmt );        
        $conn->close();
		
        $this->setHttpHeaders( "application/json", 200 );
        
	}
}
/*
controls the RESTful services
URL mapping
*/
/*
$_SERVER['REQUEST_METHOD']='GET';
$_POST=Array(["http_origin"] => " https:\/\/giptest1.epbx.com");*/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $view = "";
    if (isset($_GET["id"]))
        $view = $_GET["id"];
    if ($view == "") {
        $http_origin_handler = new HttpOriginsHandler();
        $http_origin_handler->get_records();
    } else {
        $http_origin_handler = new HttpOriginsHandler();
        $http_origin_handler->get_record($_GET["id"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data                = json_decode(file_get_contents('php://input'), true);
    $http_origin_handler = new HttpOriginsHandler();
	$count = count($data);
	#This can handle regular inserts [{}]
	if ($count == 1)
		$http_origin_handler->insert_record($data[0]);
	#This can handle regular inserts [{},{},..]	
	else
		$http_origin_handler->insert_records($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    ## This code works with first data format 
    $data                = json_decode(file_get_contents('php://input'), true);
    $http_origin_handler = new HttpOriginsHandler();
    $http_origin_handler->update_record($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    #put data here
    ## This code works with second data format 
    $count = count($_GET);
    $http_origin_handler = new HttpOriginsHandler();
	if ($count == 1)
		$http_origin_handler->delete_record($_GET);
	else
		$http_origin_handler->delete_records($_GET);
}
?>
