<?php

namespace jalder\Upnp\Roku\Applications;

class Simplevideoplayer
{

    private $appId = 'dev';

    public function __construct()
    {


    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function launchParams($arguments)
    {
        return $arguments;
    }

    public function load()
    {
        //there are no runtime load commands or communication, playback is started via launch parameters to roku application
        return true;
    }
}
