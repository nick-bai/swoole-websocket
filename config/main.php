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

    ],

    'version' => 0.1
];