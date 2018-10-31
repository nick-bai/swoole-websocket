<?php
/**
 * Created by PhpStorm.
 * User: baiyunfei
 * Date: 2018/10/31
 * Time: 10:58 AM
 */
namespace whisper;

trait EventTrait
{
    /**
     * server启动
     * @param \swoole\server $server
     */
    public function onStart(\swoole\server $server): void
    {

    }

    /**
     * worker 启动
     * @param \swoole\server $server
     * @param int $workerId
     */
    public function onWorkerStart(\swoole\server $server, int $workerId)
    {
        // linux下重命名进程
        if('Linux' == PHP_OS) {

            if($workerId >= $server->setting['worker_num']) {

                \cli_set_process_title("whisper_".($workerId - $server->setting['worker_num']));
            } else {

                \cli_set_process_title("whisper_{$workerId}");
            }
        }

    }

    /**
     * 收到信息
     * @param server $server
     * @param \swoole\websocket\frame $frame
     */
    public function onMessage(\swoole\websocket\server $server, \swoole\websocket\frame $frame): void
    {

    }

    /**
     * websocket断开时触发
     * @param \swoole\server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(\swoole\websocket\server $server, int $fd, int $reactorId): void
    {

    }

}