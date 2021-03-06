<?php

namespace FSth\Framework\Extension\ZipKin;

use whitemerry\phpkin\Identifier\SpanIdentifier;

class ClientKin
{
    protected $context;
    protected $parser;
    protected $flag;

    protected $spanId;
    protected $requestStart;

    protected $ip;
    protected $port;

    public function __construct($context)
    {
        $this->context = $context;
        $this->parser = new ParserKin($this->context);

        $this->flag = $this->parser->parse();

        $this->spanId = new SpanIdentifier();
        $this->requestStart = zipkin_timestamp();
    }

    public function needTrace()
    {
        return $this->flag;
    }

    public function traceHeader()
    {
        if (!$this->needTrace()) {
            return [];
        }
        return [
            'x-b3-traceid' => $this->parser->traceId,
            'x-b3-spanid' => ((string)$this->spanId),
            'x-b3-parentspanid' => $this->parser->traceSpanId,
            'x-b3-sampled' => ((int)$this->parser->sampled)
        ];
    }

    public function addSpan($serverName, $host, $port, $functionName)
    {
        $this->ip = $host;
        $this->port = $port;
        $this->changeToIp();
        $zipKin = new ZipKin($serverName, $this->ip, $this->port);
        $span = $zipKin->createSpan($functionName, $this->spanId, $this->requestStart);
        $this->parser->tracer->addSpan($span);
    }

    public function getTracer()
    {
        return $this->parser->tracer;
    }

    protected function changeToIp()
    {
        if (filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
            $this->ip = '127.0.0.1';
        }
    }
}