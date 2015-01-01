<?php

namespace jalder\Upnp\Chromecast\Applications;

class YouTube
{
    private $appId = 'YouTube';

    public function __construct()
    {
        //construction should take a channel, then load app and set mediasessionid and app destination id
    }
    
    public function getAppId()
    {
        return $this->appId;
    }
    
    public function getLoadMessage($videoId)
    {
        $message = [
            'type'=>'flingVideo',
            'data'=>[
                'currentTime'=>0,
                'videoId'=>$videoId
            ]
        ];
        return $message;
    }
}
