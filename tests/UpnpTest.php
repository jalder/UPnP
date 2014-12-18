<?php

use jalder\Upnp\Upnp;

class UpnpTest extends \PHPUnit_Framework_TestCase 
{
    public function testUpnp()
    {
        $upnp = new Upnp();
        $this->assertTrue($upnp->alive());
    }
}
