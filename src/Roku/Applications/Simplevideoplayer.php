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

    public functon load()
    {
        return true;
    }
}
