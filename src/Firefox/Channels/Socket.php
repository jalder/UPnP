<?php

namespace jalder\Upnp\Firefox\Channels;

class Socket
{

    private $video;
    private $host;
    private $socketPort = 9191;
    private $socket;
    private $service;

    public function __construct($host, $arguments)
    {
        $this->video = $arguments;
        $this->service = '192.168.1.104:'.$this->socketPort;
    }

    public function execute()
    {
        $payload = ['source'=>$this->video['url'],'type'=>'LOAD','title'=>'','poster'=>''];
        //send socket connection
        var_dump($payload);
        $this->socket = stream_socket_client($this->service, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);
        if($this->socket){
            $complete = false;
            while(!feof($this->socket) && !$complete){
                fwrite($this->socket, json_encode($payload));
                $complete = true;
            }
        }
    }

}
