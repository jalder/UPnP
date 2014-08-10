<?php

namespace jalder\Upnp\Roku;
use jalder\Upnp\Roku;
use jalder\Upnp\Roku\Remote;

class Player{

    private $remote;
    private $location;

    public function __construct($location)
    {
        $this->remote = new Remote($location);
        $this->location = $this->remote->getLocation();
    }

    public function play($video)
    {
        if(is_array($video)){
            $arguments = array(
                'url'=>$video['url'],
                'StreamFormat'=>$video['format'],
                'srt'=>$video['subtitle_url'],
                'title'=>$video['title'],
               
            );
            $response = $this->remote->loadChannel('dev',$arguments);
            return true;
        }
    }

}
