<?php

namespace jalder\Upnp\Mediaserver;

class Contentdirectory
{
    public $directory = [];

    public function __construct()
    {


    }

    public function getXml()
    {
        return $this->formatXml($this->directory);
    }

    public function addItem($item)
    {
        $this->directory['items'][] = $item;
        return $this;
    }
    
    public function addContainer($container)
    {
        $this->directory['containers'][] = $container;
        return $this;
    }

    public static function formatXml($directory)
    {
        $dom = new \DOMDocument();
        $root = $dom->createElement('DIDL-Lite');
        $root = $dom->appendChild($root);
        $root->setAttribute('xmlns','urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/');
        $root->setAttribute('xmlns:dc','http://purl.org/dc/elements/1.1/');
        $root->setAttribute('xmlns:upnp','urn:schemas-upnp-org:metadata-1-0/upnp/');
        foreach($directory['containers'] as $c){
            $container = $dom->createElement('container');
            $container = $root->appendChild($container);
            $container->setAttribute('id',$c['id']);
            $container->setAttribute('parentID',$c['parentID']);
            $container->setAttribute('restricted',$c['restricted']);
            $container->setAttribute('childCount',$c['childCount']);

            $title = $dom->createTextNode($c['title']);
            $dctitle = $dom->createElement('dc:title');
            $title = $dctitle->appendChild($title);
            $container->appendChild($dctitle);

            $class = $dom->createTextNode('object.container');
            $upnpclass = $dom->createElement('upnp:class');
            $class = $upnpclass->appendChild($class);
            $container->appendChild($upnpclass);
        }
        foreach($directory['items'] as $i){
            $item = $dom->createElement('item');
            $item = $root->appendChild($item);
            $item->setAttribute('id',$i['id']);
            $item->setAttribute('parentID',$i['parentID']);
            $item->setAttribute('restricted',$i['restricted']);

            $title = $dom->createTextNode($i['title']);
            $dctitle = $dom->createElement('dc:title');
            $title = $dctitle->appendChild($title);
            $item->appendChild($dctitle);

            $class = $dom->createTextNode('object.item.videoItem');
            $upnpclass = $dom->createElement('upnp:class');
            $class = $upnpclass->appendChild($class);
            $item->appendChild($upnpclass);

            $res = $dom->createTextNode($i['url']);
            $resElement = $dom->createElement('res');
            $resElement->setAttribute('protocolInfo',$i['protocolInfo']);
            $resElement->setAttribute('size',$i['size']);
            $resElement->setAttribute('duration',$i['duration']);
            $resElement->setAttribute('bitrate',$i['bitrate']);
            $resElement->setAttribute('resolution',$i['resolution']);
            $resElement->setAttribute('sampleFrequency',$i['sampleFrequency']);
            $resElement->setAttribute('nrAudioChannels',$i['nrAudioChannels']);
            $resElement->appendChild($res);
            $item->appendChild($resElement);
        }

        return $dom->saveXML($root);
    }
}
