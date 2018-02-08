<?php

require_once( "SimpleRest.php" );

class permissionHandler extends SimpleRest{
	function execute_command($command){
		$pos=false;
		exec($command,$output,$returncode);	
		foreach ($output as $value){			
		$pos=strpos($value,'error');
		if ($pos !== false){
			break;			
			}
		}

		if ($returncode ==0 and $pos === false)
		{
			$statusCode = 200;
			$this->setHttpHeaders( "application/json", $statusCode );
			$rows     = array(
				'Output' => json_encode($output),
				'ReturnCode' => $returncode 
			);
			$response = json_encode( $rows );
			echo $response;
			return;
		}	
		else{
			$statusCode = 503;
			$this->setHttpHeaders( "application/json", $statusCode );
			$rows     = array(
				'error' => json_encode($output),
				'ReturnCode' => $returncode 
			);
			$response = json_encode( $rows );
			echo $response;
			return;
		}

	}
	
	function error($output){
		$statusCode = 404;
			$this->setHttpHeaders( "application/json", $statusCode );
			$rows     = array(
				'error' => $output 
			);
			$response = json_encode( $rows );
			echo $response;
			return;
	}
}

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
	$data         = json_decode( file_get_contents( 'php://input' ), true );
	$permission_handler= new permissionHandler();
	if ( isset( $data[ "kamcmd" ] ) )
	{
        $command = $data[ "kamcmd" ];
		if ($command == 'permissions')
		{
			$permission_handler->execute_command('kamcmd permissions.addressReload');
		}
		elseif($command == 'sip_agents')
		{
			$permission_handler->execute_command('kamcmd htable.reload user_agents');
		}
		elseif($command == 'domains')
		{
			$permission_handler->execute_command('kamcmd domain.reload');
		}
		elseif($command == 'dids')
		{
			$permission_handler->execute_command('kamcmd htable.reload dids');
		}
		else
		{
			$permission_handler->error("Invalid command");			
		}
	}
	else{
		$permission_handler->error("Invalid Data Format(Post Json data with kamcmd feild)");					
	}
	
	
}
?>