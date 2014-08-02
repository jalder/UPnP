<?php

namespace jalder\Upnp;

class Mediaserver extends Core
{

    public function discover()
    {
        return parent::search('urn:schemas-upnp-org:device:MediaServer:1');
    }

    public function filter($devices)
    {

    }
}
