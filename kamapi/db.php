<?php

Class DB
{
    private $servername = "localhost";
    private $username = "kamailio";
    private $password = "__PASSPHRASE__";
    private $dbname = "kamailio";
    private $conn = Null;

    function connect()
    {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
            return Null;
        }
        return $this->conn;
    }

    function get_result($Statement)
    {
        $RESULT = array();
        $Statement->store_result();
        for ($i = 0; $i < $Statement->num_rows; $i++) {
            $Metadata = $Statement->result_metadata();
            $PARAMS = array();
            while ($Field = $Metadata->fetch_field()) {
                $PARAMS[] = &$RESULT[$i][$Field->name];
            }
            call_user_func_array(array($Statement, 'bind_result'), $PARAMS);
            $Statement->fetch();
        }
        return $RESULT;
    }
}

?>