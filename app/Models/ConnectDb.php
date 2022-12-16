<?php

    namespace App\Models;
    class ConnectDb
    {
        private static $instance = null;
        private $connection;

        private function __construct(){
            $this->connection = mysqli_connect(DBHOSTNAME, DBUSERNAME, DBPASSWORD, DBNAME);
            $this->connection->query("SET NAMES utf8");
        }

        public static function getInstance(){
            if(!self::$instance){
                self::$instance = new ConnectDb();
            }

            return self::$instance;
        }

        public function getConnection(){
            return $this->connection;
        }
    }

?>