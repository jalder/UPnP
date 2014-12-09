<?php

namespace jalder\Upnp\Roku\Applications;

use jalder\Upnp\Roku\Remote;
use jalder\Upnp\Firefox\Channels;

class Firefox
{
    private $appId;
    private $protocolVersion = 1;
    private $socketPort = 9191;
    private $sources = ['homescreen', 'app-run-dev', 'external-control'];
    private $video;
    private $remote;

    public function __construct($device)
    {
        $this->remote = new Remote($device);
        foreach($this->remote->getChannels() as $id=>$ch){
            if($ch['name'] == 'Firefox'){
                $this->appId = $id;
            }
        }

    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function launchParams($arguments)
    {
        $this->video = $arguments;
        return ['source'=>$this->sources[2], 'version'=>$this->protocolVersion];
    }

    public function load()
    {
        $channel = new Channels\Socket($this->remote->getLocation(),$this->video);
        $channel->execute();
    }
}