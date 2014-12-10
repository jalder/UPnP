<?php

namespace jalder\Upnp\Roku\Applications;
use jalder\Upnp\Roku\Remote;

class YouTube
{
    private $remote;
    private $appId;

    public function __construct($device)
    {
        $this->remote = new Remote($device);
        foreach($this->remote->getChannels() as $id=>$ch){
            if($ch['name'] == 'YouTube'){
                $this->appId = $id;
            }
        }
    }

    public function getAppId()
    {
        return $this->$appId;
    }

    public function launchParams($arguments)
    {
        $this->video = $arguments;
        return ['v'=>$this->video['id']];
    }

    public function load()
    {
        return true;
    }
}
