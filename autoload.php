<?php
/**
 * 自动加载
 * @param $name
 */
function autoload($name) {

    $targetFile =  str_replace('\\', '/', $name);
    $file = realpath(__DIR__) . '/' . $targetFile . '.php';

    if(file_exists($file)) {

        require_once($file);
    }
}

spl_autoload_register('autoload');

include "./vendor/autoload.php";
