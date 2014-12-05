<?php

namespace jalder\Upnp\Roku\Applications;
use jalder\Upnp\Roku;

class YouTube
{

    private $appId;

    public function __construct($device)
    {
        $remote = new Remote($device);
        foreach($remote->getChannels() as $id=>$ch){
            if($ch['name'] == 'YouTube'){
                $this->appId = $id;
            }
        }
    }

    public function getAppId()
    {
        return $this->$appId;
    }

}
