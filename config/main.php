<?php
/**
 * Created by PhpStorm.
 * User: baiyunfei
 * Date: 2018/10/30
 * Time: 4:15 PM
 */

return [

    'server' => [
        'worker_num' => 4,
        'daemonize' => 0,
        'log_file' => __DIR__ . '/../logs/' . date('Y-m-d') . '.log',
        'pid_file' => __DIR__ . '/../whisper_websocket_pid.pid',
        'log_level' => 0
    ],

    'port' => 8991,

    'db' => [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '127.0.0.1',
        // 数据库名
        'database'        => 'whisper',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => 'root',
        // 端口
        'hostport'        => '3306',
        // 数据库编码默认采用utf8
        'charset'         => 'utf8'
    ],

    'version' => 0.1
];