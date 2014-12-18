<?php

namespace jalder\Upnp\Roku\Channels;

class Firefox
{
    private $video;
    private $socket;
    private $service;

    public function __construct($host, $arguments)
    {
        $this->video = $arguments;
        $this->service = $host;
    }

    public function execute()
    {
        $payload = ['source'=>$this->video['url'],'type'=>'LOAD','title'=>'','poster'=>''];
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
