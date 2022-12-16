<?php

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, lazyUpdate');
    header('Content-type: text/html; charset="utf-8"',true);

    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/config/helpers.php';
    require_once __DIR__ . '/config/router.php';
    require_once __DIR__ . '/../../config.php';

    use Pecee\SimpleRouter\SimpleRouter as Router;
    Router::setDefaultNamespace('App\Controllers');
    try {
        Router::start();
    }
    catch(Throwable $e){
        print_r($e);
    }
    
?>