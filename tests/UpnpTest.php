<?php

use Jalder\Upnp\Upnp;

class UpnpTest extends PHPUnit_Framework_TestCase 
{
    public function testUpnp()
    {
        $upnp = new Upnp();
        //$upnp->alive();
        $this->assertTrue($upnp->alive());
    }
}
