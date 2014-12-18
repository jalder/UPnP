<?php

use jalder\Upnp\Roku;
use jalder\Upnp\Roku\Remote;

class RokuTest extends \PHPUnit_Framework_TestCase
{
    public function testRoku()
    {
        $roku = new Roku();
        $devices = $roku->discover();
        $this->assertTrue((bool)$roku);
        foreach($devices as $d){
            $remote = new Remote($d['location']);
            $this->assertTrue((bool)$remote->getChannels());
        }
    }
}
