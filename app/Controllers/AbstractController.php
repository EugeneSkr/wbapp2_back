<?php
    namespace App\Controllers;

    use Pecee\Http\Request;
    use Pecee\Http\Response;
    use Pecee\SimpleRouter\SimpleRouter as Router;

    use App\Models\ConnectDb;

    abstract class AbstractController
    {
        protected $response;
        protected $request;
        protected static $db;
        protected static $time;
        protected static $userIp;


        public function __construct()
        {
            $this->request = Router::router()->getRequest();
            $this->response = new Response($this->request);
            self::$db = ConnectDb::getInstance()->getConnection();
            self::$time = time();
            self::$userIp =  $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
        }
    }

?>