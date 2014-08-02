<?php

namespace jalder\Upnp\Mediaserver;
use jalder\Upnp\Mediaserver;


class Browse
{

    public $ctrlurl;
    private $upnp;

    public function __construct($server)
    {
        $this->upnp = new Mediaserver();
        if(is_array($server['description']['device']['serviceList']['service'])){
            foreach($server['description']['device']['serviceList']['service'] as $service){
                if($service['serviceId'] == 'urn:upnp-org:serviceId:ContentDirectory'){
                    $this->ctrlurl = $this->upnp->baseUrl($server['location']).$service['controlURL'];
                }
            }
        }
        //else if is ctrlurl ready to go... maybe set
    }

    public function browse($base = '0', $browseflag = 'BrowseDirectChildren', $start = 0, $count = 0)
    {
        libxml_use_internal_errors(true); //is this still needed?
        $args = array(
            'ObjectID'=>$base,
            'BrowseFlag'=>$browseflag,
            'Filter'=>'',
            'StartingIndex'=>$start,
            'RequestedCount'=>$count,
            'SortCriteria'=>'',
        );
        $response = $this->upnp->sendRequestToDevice('Browse', $args, $this->ctrlurl, $type = 'ContentDirectory');

        if($response){
            $doc = new \DOMDocument();
            $doc->loadXML($response);
            $containers = $doc->getElementsByTagName('container');
            $directories = array();
            foreach($containers as $container){
                //var_dump($container);
                //var_dump($container->attributes);
                foreach($container->attributes as $attr){
                    //var_dump($attr);
                    if($attr->name == 'id'){
                        $id = $attr->nodeValue;
                    }
                }
                foreach($container->childNodes as $property){
                    //var_dump($property); //will need to do attributes to get id here as well
                    foreach($property->attributes as $attr){
                        var_dump($attr);
                    }
                    $directories[$id][] = $property;
                }
            }
            var_dump($directories);
        }

        return false;
        if(isset($response['body']['h1'])){
            return false;
        }
        $returnd = $response['s:Body']['u:BrowseResponse']['NumberReturned'];
        $total = $response['s:Body']['u:BrowseResponse']['TotalMatches'];
        $result = $response['s:Body']['u:BrowseResponse']['Result'];
        return $result;
    }

}
