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
        print('welcome to the cli');
        //start socket connection
        $socket = new Channels\Socket($this->host, 'daemon');
        $socket->execute();
        //read from redis queue and take action on new tasks
    }
}
