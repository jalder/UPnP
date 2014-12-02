<?php

namespace jalder\Upnp\Chromecast\Applications;

class DefaultMediaReceiver
{

    private $appId = 'CC1AD845';

    public function __construct()
    {
        //construction should take a channel, then load app and set mediasessionid and app destination id
    }

    public function getAppId()
    {
        return $this->appId;
    }
}
