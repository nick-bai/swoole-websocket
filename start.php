<?php
/**
 * Created by PhpStorm.
 * User: baiyunfei
 * Date: 2018/10/30
 * Time: 3:51 PM
 */
use whisper\Websocket;

require './autoload.php';

$config = include './config/main.php';

$server = new Websocket();

$server->setting($config);
$server->run();