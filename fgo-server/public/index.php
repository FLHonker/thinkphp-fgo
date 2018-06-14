<?php

ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('PRC'); 
define("APP_PATH", realpath(dirname(__FILE__) . '/../').'/'); /* 指向public的上一级 windows linux区别*/
define("STATIC_PATH",  'http://fgo.my.com');
// define("STATIC_PATH",  'http://weekly.bmmyou.com');

define("VIEW_PATH",  APP_PATH . "application/views/");
define("CONCTROLLER_PATH",  APP_PATH . "application/views/");
// echo VIEW_PATH;
isset($_SESSION) || session_start();
    $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : ''; 
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }

$app = new Yaf_Application(APP_PATH . "/conf/application.ini","develop");
$app->bootstrap()->run();