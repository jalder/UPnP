<?php

namespace jalder\Upnp;

class Renderer extends Core
{

    public function discover()
    {
        return parent::search('urn:schemas-upnp-org:service:AVTransport:1');
    }

    /**
     * if a previous ran upnp core search is available in memory, just filter for the renderers
     *
     */

    public function filter($results = array())
    {
        if(is_array($results)){
            foreach($results as $usn=>$device){
                if($device['st'] !== 'urn:schemas-upnp-org:service:AVTransport:1'){
                    unset($results[$usn]);
                }
            }
        }
        return $results;
    }
}

