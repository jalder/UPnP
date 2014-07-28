<?php

use jalder\Upnp\Upnp;
use jalder\Upnp\Core;
use jalder\Upnp\Roku;
use jalder\Upnp\Roku\Remote;

class UpnpTest extends PHPUnit_Framework_TestCase 
{
    public function testUpnp()
    {
        $upnp = new Upnp();
        //$upnp->alive();
        $this->assertTrue($upnp->alive());
    }

    public function testRoku()
    {
        $roku = new Roku();
        $devices = $roku->discover();
        $this->assertTrue((bool)$roku);
        foreach($devices as $d){
            $remote = new Remote($d['location']);
            var_dump($remote->getChannels());
        }
    }
}
