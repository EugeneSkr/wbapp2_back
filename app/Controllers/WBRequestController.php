<?php
    namespace App\Controllers;

    class WBRequestController
    {
        private $url;
        private $headers;
        private $isPost;
        private $json;
        private $method;

        public function __construct(string $url, array $headers, bool $isPost = false, string $json = '', string $method = '')
        {
            $this->url = WBAPIPATH.$url;
            $this->headers = $headers;
            $this->headers[] = 'Authorization: '.SECRETKEY;
            $this->isPost = $isPost;
            $this->json = $json;
            $this->method = $method;
        }

        //отправка и получение данных от API Wildberries
        public function sendRequest():array
        {
            $curl = curl_init($this->url);
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
            if($this->method){
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
            }
            if($this->isPost){
                curl_setopt($curl, CURLOPT_POST, 1);
            }
            if($this->json){
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->json);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response, true);
        }
    }
?>