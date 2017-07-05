<?php

namespace FSth\Framework\Extension\ZipKin;

use whitemerry\phpkin\Logger\SimpleHttpLogger;

class RequestKin
{
    protected $config;
    protected $execute;

    protected $traceLogger;

    protected $server;

    protected $traceId;
    protected $traceSpanId;
    protected $isSampled;

    protected $uri;

    /**
     * ServerKin constructor.
     * @param $serverName
     * @param $host
     * @param $port
     * @param $config
     *  logger_type http
     *  host
     */
    public function __construct($serverName, $host, $port, $config)
    {
        $this->config = $config;

        $this->initTraceLogger();

        $this->server = new ZipKin($serverName, $host, $port);
        $this->server->setTraceLogger($this->traceLogger);
    }

    public function setRequestServer($requestServer)
    {
        $this->traceId = !empty($requestServer['http_x_b3_traceid']) ?
            $requestServer['http_x_b3_traceid'] : null;
        $this->traceSpanId = !empty($requestServer['http_x_b3_spanid']) ?
            $requestServer['http_x_b3_spanid'] : null;
        $this->isSampled = !empty($requestServer['http_x_b3_sampled']) ? false : true;

        $this->server->setBackEndParams($this->traceId, $this->traceSpanId, $this->isSampled);

        $this->uri = $requestServer['request_uri'];
    }

    public function getTracer()
    {
        $tracer = $this->server->createTracer($this->uri, false);
        return $tracer;
    }

    public function getTraceId()
    {
        return $this->traceId;
    }

    public function getTraceSpanId()
    {
        return $this->traceSpanId;
    }

    public function getSampled()
    {
        return $this->isSampled;
    }

    protected function initTraceLogger()
    {
        if ($this->config['logger_type'] == 'http') {
            $this->traceLogger = new SimpleHttpLogger([
                'host' => $this->config['host'],
                'muteErrors' => false,
            ]);
        }
    }

}