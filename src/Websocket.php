<?php
/**
 * Created by PhpStorm.
 * User: baiyunfei
 * Date: 2018/10/30
 * Time: 3:51 PM
 */
namespace whisper;

use Inhere\Console\IO\Output;
use swoole\websocket\server;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Websocket
{
    use EventTrait;

    private $config = [];

    private $server;

    private $output;

    private $db;

    private $log;

    /**
     * 运行服务器
     */
    public function run(): void
    {
        $this->checkSapiEnv();
        $this->parseCommand() &&  $this->start();
    }

    /**
     * 设置server属性
     * @param array $config
     */
    public function setting(array $config = []): void
    {
        empty($config) && $config = [
            'server' => [
                'worker_num' => 1,
                'daemonize' => 0
            ],

            'port' => 8991
        ];

        $this->config = $config;

        $this->output = new Output();

        $this->log = new Logger('whisper_log');
        $this->log->pushHandler(new StreamHandler($this->config['server']['log_file']));
    }

    /**
     * 检测环境
     */
    protected function checkSapiEnv(): void
    {
        if (php_sapi_name() != "cli") {
            exit("only run in command line mode " . PHP_EOL);
        }
    }

    /**
     * 解析输入参数
     */
    private function parseCommand(): bool
    {
        global $argv;

        $availableCommands = [
            'start',
            'stop',
            'restart',
            'reload',
            'status',
            'connections',
        ];


        if (!isset($argv[1]) || !in_array($argv[1], $availableCommands)) {
            if (isset($argv[1])) {

                $msg = 'Unknown command: ' . $argv[1];
                $this->output->writeln($msg);
                $this->log->error($msg);
            }

            $this->helper();
        }

        $command  = trim($argv[1]);
        $command2 = isset($argv[2]) ? $argv[2] : '';

        switch ($command) {
            case 'start':

                if($this->isRunning()) {

                    $this->output->writeln('');
                    $msg = 'server is running, please run stop or restart if you want to restart server';
                    $this->output->writeln("<red>' . $msg . '</red>");
                    $this->log->error($msg);

                    return false;
                }

                if ($command2 === '-d') {

                    $this->config['server']['daemonize'] = 1;
                }

                return true;
                break;
            case 'stop':

                $this->stop();
                exit(0);

                break;
            case 'restart':

                if ($command2 === '-d') {

                    if($this->restart()) {
                        return false;
                    }
                }else {

                    $this->config['server']['daemonize'] = 0;
                    $this->stop();
                    return true;
                }

                break;
        }

        return true;
    }

    /**
     * 终止服务器
     */
    private function stop(): bool
    {
        $pidFile = $this->config['server']['pid_file'];

        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);

            $sig = SIGTERM;
            if (!\swoole\process::kill($pid, 0)) {

                $this->output->writeln('');
                $msg = "PID :{$pid} not exist";
                $this->output->writeln("<red>" . $msg . "</red>");
                $this->log->error($msg);

                return false;
            }

            \swoole\process::kill($pid, $sig);

            // 等待5秒
            $time = time();
            $flag = false;
            while (true) {
                usleep(1000);
                if (!\swoole\process::kill($pid, 0)) {

                    $this->output->writeln('');
                    $msg = "server stop at " . date("Y-m-d H:i:s");
                    $this->output->writeln("<info>" . $msg . "</info>");
                    $this->log->error($msg);

                    if (is_file($pidFile)) {
                        unlink($pidFile);
                    }
                    $flag = true;
                    break;
                } else {

                    if (time() - $time > 5) {

                        $this->output->writeln('');
                        $msg = 'stop server fail.try again ';
                        $this->output->writeln("<yellow>' . $msg . '</yellow>");
                        $this->log->warning($msg);

                        break;
                    }
                }
            }
            return $flag;

        } else {

            $this->output->writeln('');
            $msg = 'pid 文件不存在，请执行查找主进程pid,kill!';
            $this->output->writeln("<red>" . $msg . "</red>");
            $this->log->error($msg);

            return false;
        }
    }

    /**
     * 重启服务器
     */
    private function restart(): bool
    {
        $pidFile = $this->config['server']['pid_file'];

        if (file_exists($pidFile)) {
            $sig = SIGUSR1;
            $pid = file_get_contents($pidFile);
            if (!\swoole\process::kill($pid, 0)) {

                $this->output->writeln('');
                $msg = "pid :{$pid} not exist";
                $this->output->writeln("<red>" . $msg . "</red>");
                $this->log->error($msg);

                return false;
            }

            \swoole\process::kill($pid, $sig);

            $this->output->writeln('');
            $msg = "send server reload command at " . date("Y-m-d H:i:s");
            $this->output->writeln("<info>" . $msg . "</info>");
            $this->log->info($msg);

            return true;
        } else {

            $this->output->writeln('');
            $msg = 'pid 文件不存在，请执行查找主进程pid,kill!';
            $this->output->writeln("<red>" . $msg . "</red>");
            $this->log->error($msg);

            return false;
        }
    }

    /**
     * 帮助信息
     */
    private function helper(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<red>command error</red>');
        $this->output->writeln('');
        $this->output->writeln('<yellow>Usage:</yellow>');
        $this->output->writeln('');
        $this->output->writeln('    php start.php <info>{command}</info> [-d]');
        $this->output->writeln('');
        $this->output->writeln('<yellow>Available Commands:</yellow>');
        $this->output->writeln('');

        $this->output->writeln('    <info>start        start the server</info>');
        $this->output->writeln('    <info>start -d     run as a daemon</info>');
        $this->output->writeln('    <info>stop         stop the server</info>');
        $this->output->writeln('    <info>restart      restart the server</info>');
        $this->output->writeln('    <info>restart -d   restart the server and run as a daemon</info>');
        $this->output->writeln('');

        $this->output->writeln('<red>more information please to see: http://doc.baiyf.com</red>');
        $this->output->writeln('');

        $this->log->error('command error');
        exit();
    }

    /**
     * 运行服务
     */
    private function start(): void
    {
        $this->server = new server("0.0.0.0", $this->config['port']);

        $this->server->set($this->config['server']);

        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);

        $this->display();

        $this->log->info('server start and bind ' . $this->config['port'] . ' with worker ' . $this->config['server']['worker_num']);

        $this->server->start();
    }

    /**
     * 展示运行数据
     */
    private function display(): void
    {
        $this->output->writeln('');
        $this->output->writeln('-----------------------<info> whisper </info>-----------------------');
        $this->output->writeln('whisper server version: 0.1   php version: ' . PHP_VERSION);
        $this->output->writeln('-----------------------<info>  info   </info>-----------------------');
        $this->output->writeln('listen                    worker            status');
        $this->output->writeln('websocket://0.0.0.0:' . $this->config['port'] . '    ' . $this->config['server']['worker_num'] . '                 <info>ok</info>');
        $this->output->writeln('-------------------------------------------------------');
        $this->output->writeln('');
        $this->output->writeln('Press Ctrl+C to stop. Start success.');
    }

    /**
     * server 是否在运行
     * @return bool
     */
    private function isRunning(): bool
    {
        $pidFile = $this->config['server']['pid_file'];

        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (\swoole\process::kill($pid, 0)) {

                return true;
            }

            return false;
        }

        return false;
    }
}