<?php

use jalder\Upnp\Mediaserver;

class MediaserverTest extends \PHPUnit_Framework_TestCase
{

    public function testContentdirectory()
    {
        $cd = new Mediaserver\Contentdirectory();
        $xml = $cd->addItem($this->getItemExample())
            ->addContainer($this->getContainerExample())
            ->getXml();
        //asset xml = mock
        $this->assertEquals($xml, $this->getMockContents(''));
    }

    private function getMockContents($file)
    {
        return trim(file_get_contents(dirname(__FILE__).'/mock/mediatomb/upnp/contentdirectory/browse.browseDirectChildren.xml'));
    }

    private function getItemExample()
    {
        $item = [
            'id' => 6946,
            'parentID' => 6945,
            'restricted' => 1,
            'title' => '28 Days Later.avi',
            'protocolInfo' => 'http-get:*:video/x-msvideo:*',
            'size' => '733155328',
            'duration' => '01:48:29.2',
            'bitrate' => '112633',
            'resolution' => '592x320',
            'sampleFrequency' => '48000',
            'nrAudioChannels' => 2,
            'url' => 'http://192.168.1.20:49152/content/media/object_id/6946/res_id/0/ext/file.avi'
        ];

        return $item;
    }

    private function getContainerExample()
    {
        $container = [
            'id' => 10,
            'parentID' => 7,
            'restricted' => 1,
            'childCount' => 15,
            'title' => 'Directories'
        ];

        return $container;
    }
}
