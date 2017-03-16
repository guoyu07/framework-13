<?php
/**
 * Created by PhpStorm.
 * User: lihan
 * Date: 17/1/12
 * Time: 16:46
 */
namespace FSth\Framework\Context;

class Server
{
    private $params;

    private $method;
    private $config;

    private $validMethod;

    public function __construct($params)
    {
        $this->params = $params;
        $this->validMethod = array('start', 'stop');
    }

    public function handle()
    {
        $this->init();
        call_user_func_array(array($this, $this->method), array());
    }

    private function init()
    {
        if (count($this->params) != 3) {
            $this->method = "show";
            return;
        }
        $this->method = $this->params[1];
        if (!in_array($this->method, $this->validMethod)) {
            $this->method = "show";
            return;
        }

        $configFile = $this->params[2];
        if (!file_exists($configFile)) {
            $this->method = "show";
            return;
        }

        $this->config = include $configFile;
    }

    private function start()
    {
        if (file_exists($this->config['pid_file'])) {
            echo "server is already start \n";
            return;
        }
        echo "server is starting...";
        $kernel = include $this->config['bootstrap'];
        $kernel->setConfig('server', $this->config);

        $protocol = new $this->config['protocol']($kernel);

        $serverConfig = $kernel->config('server');

        $server = new $this->config['server']($serverConfig['host'], $serverConfig['port']);
        if (!empty($serverConfig['tcpPort']) && method_exists($server, 'setTcp')) {
            $serverConfig['tcpSetting'] = !empty($serverConfig['tcpSetting']) ? $serverConfig['tcpSetting'] : [];
            $server->setTcp($serverConfig['tcpPort'], $serverConfig['tcpSetting']);
        }
        $server->setKernel($kernel);
        $server->setProtocol($protocol);
        $server->setLogger($kernel['logger']);

        if ($serverConfig['daemonize']) {
            $server->daemonize();
        }

        $server->listen();
    }

    private function stop()
    {
        if (file_exists($this->config['pid_file'])) {
            $pid = intval(file_get_contents($this->config['pid_file']));
            if ($pid && posix_kill($pid, SIGTERM)) {
                while (1) {
                    if (file_exists($this->config['pid_file'])) {
                        sleep(1);
                        continue;
                    }

                    echo "server stop\n";
                    break;
                }
            }
        }
    }

    private function show()
    {
        echo "Usage: php {$this->params[0]} start|stop pathToConfig\n";
    }
}