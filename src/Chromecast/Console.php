<?php

namespace jalder\Upnp\Chromecast;

class Console
{
    private $host;

    public function __construct($host)
    {
        $this->host = $host;
    }

    public function run()
    {
        $socket = new Channels\Socket($this->host, 'daemon', $verbosity = 5, $channel = 'sqlite');
        $socket->execute();
    }
}
