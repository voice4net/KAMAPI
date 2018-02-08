<?php


/*
URL: https://uchkam.epbx.com/kamapi/dids.php
Header            :  Accept : application/json 
Post Data Format  : [{"did":"9999999999","destination_domain":"127.0.0.1"}]
Put Data Format   : {"id":4,"did":"9999999999","destination_domain":"127.0.0.1","active":"1"} 
Delete Data Format: {"id":21}
*/

require_once( "SimpleRest.php" );
require_once( "db.php" );
class DidsHandler extends SimpleRest
{
    function encodeXml( $responseData )
    {
        $records = new SimpleXMLElement( '<?xml version="1.0"?><records></records>' );
        foreach ( $responseData as $key => $value ) {
            $record = $records->addChild( "record" );
            $record->addChild( "id", $value[ "id" ] );
            $record->addChild( "did", $value[ "did" ] );
            $record->addChild( "destination_domain", $value[ "destination_domain" ] );
            $record->addChild( "active", $value[ "active" ] );
            $record->addChild( "modified", $value[ "modified" ] );
        }
        return $records->asXML();
    }
    function encodeHtml( $responseData )
    {
        $htmlResponse = "<table border='1'>";
        foreach ( $responseData as $key => $value ) {
            $htmlResponse .= "<tr><td>" . $key . "</td><td>" . $value[ "did" ] . "</td><td>" . $value[ "destination_domain" ] . "</td><td>" . $value[ "active" ] . "</td><td>" . $value[ "modified" ] . "</td></tr>";
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
    function get_records( )
    {
        /*
        1. This function can handle all valid Get requests
        2. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        */
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
        $sql    = "SELECT id, did, destination_domain, active, modified FROM did_routing;";
        $result = $conn->query( $sql );
        $rows   = array( );
        while ( $r = mysqli_fetch_assoc( $result ) ) {
            $rows[ ] = $r;
        }
        $requestContentType = $_SERVER[ 'HTTP_ACCEPT' ];
        $this->setHttpHeaders( $requestContentType, $statusCode );
        if ( strpos( $requestContentType, 'text/html' ) !== false ) {
            $response = $this->encodeHtml( $rows );
            echo $response;
        } else if ( ( strpos( $requestContentType, 'application/xml' ) !== false ) || ( strpos( $requestContentType, 'text/xml' ) !== false ) ) {
            $this->setHttpHeaders( 'application/xml', $statusCode );
            $response = $this->encodeXml( $rows );
            echo $response;
        } else {
            #This case is either request type is json or empty
            $this->setHttpHeaders( "application/json", $statusCode );
            $response = json_encode( $rows );
            echo $response;
        }
        $conn->close();
    }
    function get_record( $id )
    {
        /*        
        1. This function would send data in xml/json/html format, default is json based on HTTP_ACCEPT header
        2. This can handle invalid id  or empty 
        3. This function can handle SQL Injection
        */
        $rows = array( );
        $db   = new DB();
        if ( $id == Null ) {
            $statusCode = 400;
            $rows       = array(
                 'error' => 'Invalid Request Format',
                'SampleURLFormat' => '{"URL": "https://uchkam.epbx.com/kamapi/dids.php?id=10","headers" : "HTTP_ACCEPT: text/html or application/json or application/xml"}' 
            );
            $response   = json_encode( $rows );
            echo $response;
            return;
        }
        $conn = $db->connect();
        if ( empty( $conn ) ) {
            $statusCode = 404;
            $rows       = array(
                 'error' => 'No databases found!' 
            );
            $response   = json_encode( $rows );
            echo $response;
            return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare( "SELECT id, did, destination_domain, active, modified FROM did_routing where id=?" );
        $stmt->bind_param( 's', $id );
        $stmt->execute();
        $result = $db->get_result( $stmt );
        $conn->close();
        while ( $r = array_shift( $result ) ) {
            $rows[ ] = $r;
        }
        if ( $rows == Null ) {
            $statusCode = 404;
            $rows       = array(
                 array(
                     'error' => 'No matching did found with the given id' 
                ) 
            );
            $response   = json_encode( $rows );
            echo $response;
            return;
        }
        $requestContentType = $_SERVER[ 'HTTP_ACCEPT' ];
        $this->setHttpHeaders( $requestContentType, $statusCode );
        if ( strpos( $requestContentType, 'text/html' ) !== false ) {
            $response = $this->encodeHtml( $rows );
            echo $response;
        } else if ( ( strpos( $requestContentType, 'application/xml' ) !== false ) || ( strpos( $requestContentType, 'text/xml' ) !== false ) ) {
            $response = $this->encodeXml( $rows );
            echo $response;
        } else {
            #This case is either request type is json or invalid HTTP_ACCEPT
            $this->setHttpHeaders( "application/json", $statusCode );
            $response = json_encode( $rows[ 0 ] );
            echo $response;
        }
    }
    function insert_record( $post_data )
    {
        /*
        1. This function can handle valid insertion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. Did duplication
        */
        if ( isset( $post_data[ "did" ] ) AND isset( $post_data[ "destination_domain" ] ) ) {
            $did                = $post_data[ "did" ];
            $destination_domain = $post_data[ "destination_domain" ];
            if ( $did == "" or $did == " " or $destination_domain == "" or $destination_domain == " " ) {
                $this->invalid_data_format( '[{"did":"9999999999","domain":"127.0.0.1"}]' );
                return;
            }
        } else {
            $this->invalid_data_format( '[{"did":"9999999999","domain":"127.0.0.1"}]' );
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
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare( "call insert_did(?,?)" );
        $stmt->bind_param( 'ss', $did, $destination_domain );
        $stmt->execute();
        $result = $db->get_result( $stmt );
        $conn->close();
        $rows = array( );
        while ( $r = array_shift( $result ) ) {
            $rows[ ] = $r;
        }
        if ( $rows == Null ) {
            $statusCode = 409;
            $rows       = array(
                 array(
                     'error' => 'did(' . $did . ') already exists in table' 
                ) 
            );
        }
        $this->setHttpHeaders( "application/json", $statusCode );
        $response = json_encode( $rows[ 0 ] );
        echo $response;
    }
	
	function insert_records( $post_data_items )
    {
        /*
        1. This function can handle valid insertion
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. User_agent duplication
        */
		$number_of_rows_inserted = 0;		
		foreach($post_data_items as $post_data){
		
			if ( isset( $post_data[ "did" ] ) AND isset( $post_data[ "destination_domain" ] ) ) {
				$did = $post_data[ "did" ];
				$destination_domain = $post_data[ "destination_domain" ];
				if ( $did == "" or $did == " " or $destination_domain == "" or $destination_domain == " " ) {
					continue;
				}
			} else {				
				$this->invalid_data_format( '[{"did":"9999999999","domain":"127.0.0.1"},{"did":"9999999999","domain":"127.0.0.1"}]' );
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
			$stmt = $conn->prepare( "call insert_did(?,?)" );
			$stmt->bind_param( 'ss', $did, $destination_domain );
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
	
    function update_record( $put_data )
    {
        /*
        1. This function can handle valid updation(update only did)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. Did duplication
        5. If Did is valid and id is invalid then function doesn't update or insert data(you have to use post)
        */
        $rows = array( );
        if ( isset( $put_data[ "id" ] ) ) {
            $id                 = $put_data[ "id" ];
            $did                = $put_data[ "did" ];
            $destination_domain = $put_data[ "destination_domain" ];
            $active             = $put_data[ "active" ];
            if ( ( ( $did == "" or $did == " " ) and ( !is_null( $did ) ) ) or $id <= 0 or ( ( $active != 0 and $active != 1 ) and $active != NULL ) ) {
                $this->invalid_data_format( '{"id":4,"did":"9999999999","destination_domain":"127.0.0.1","active":"1"}' );
                return;
            }
        } else {
            $this->invalid_data_format( '{"id":4,"did":"9999999999","destination_domain":"127.0.0.1","active":"1"}' );
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
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare( "call update_did(?,?,?,?)" );
        $stmt->bind_param( 'isss', $id, $did, $destination_domain, $active );
        $stmt->execute();
        $result = $db->get_result( $stmt );
        $conn->close();
        while ( $r = array_shift( $result ) ) {
            $rows[ ] = $r;
        }
        if ( $rows == Null ) {
            $statusCode = 409;
            $rows       = array(array(
                 'error' => 'Did(' . $did . ') already exists in table or No such id exists in the table' 
            ));
        }
        $this->setHttpHeaders( "application/json", $statusCode );
        $response = json_encode( $rows[0] );
        echo $response;
    }
    function delete_record( $_DELETE )
    {
        /*
        1. This function can handle valid deletion(there should be a matching record in database with id and did)
        2. Invalid Data Formats such as missing feilds or empty feilds
        3. Sql Injection
        4. Did duplication
        */
        $rows = array( );
		$delete_data = json_decode($_DELETE["0"],true);
        if ( isset( $delete_data[ "id" ] ) ) {
            $id = $delete_data[ "id" ];
            if ( $id <= 0 ) {
				$this->invalid_data_format( array(
					'error' => 'Invalid Data Format',
					'SampleJsonDataFormat' => '[{"id":24}]'
				) );
				return;                
            }
        } else {
            $this->invalid_data_format( array(
					'error' => 'Invalid Data Format',
					'SampleJsonDataFormat' => '{"id":24}'
				) );
			return; 
        }
        $db   = new DB();
        $conn = $db->connect();
        if ( empty( $conn ) ) {
            $statusCode = 404;
            $rawData    = array(
                 'error' => 'No databases found!' 
            );
			$response = json_encode( $rawData );
			echo $response;
			return;
        } else {
            $statusCode = 200;
        }
        $stmt = $conn->prepare( "Delete from did_routing where id=?" );
        $stmt->bind_param( 'i', $id );
        $stmt->execute();
        $conn->close();
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
			$response = json_encode( $rawData );
			echo $response;
			return;
        } else {
            $statusCode = 200;
        }
        
		$place_holder_string = substr($place_holder_string, 0, -1);
		$sql_statement = "Delete from did_routing where id IN ($place_holder_string)";
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
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'GET' ) {
    $view = "";
    if ( isset( $_GET[ "id" ] ) )
        $view = $_GET[ "id" ];
    if ( $view == "" ) {
        $dids_handler = new DidsHandler();
        $dids_handler->get_records();
    } else {
        $dids_handler = new DidsHandler();
        $dids_handler->get_record( $_GET[ "id" ] );
    }
} elseif ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $data         = json_decode( file_get_contents( 'php://input' ), true );
    $dids_handler = new DidsHandler();
    $count = count($data);
	#This can handle regular inserts [{}]
	if ($count == 1)
		$dids_handler->insert_record( $data[0]);
	#This can handle regular inserts [{},{},..]	
	else
		$dids_handler->insert_records( $data );
} elseif ( $_SERVER[ 'REQUEST_METHOD' ] === 'PUT' ) {
    ## This code works with first data format 
    $data         = json_decode( file_get_contents( 'php://input' ), true );
    $dids_handler = new DidsHandler();
    $dids_handler->update_record( $data );
} elseif ( $_SERVER[ 'REQUEST_METHOD' ] === 'DELETE' ) {	
	$count = count($_GET);    
    $dids_handler = new DidsHandler();
	if ($count == 1)    
		$dids_handler->delete_record( $_GET );
	else
		$dids_handler->delete_records( $_GET );
}
?>
