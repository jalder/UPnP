<?php

namespace jalder\Upnp\Mediaserver;

class Browser
{

    public $ctrlurl;
    private $upnp;

    public function __construct($ctrlurl)
    {
        $this->upnp = new Core();
        $this->ctrlurl = $ctrlurl;
    }

}
