<?php

namespace jalder\Upnp;

class Chromecast extends Core
{

    public function discover()
    {
        return parent::search('urn:dial-multiscreen-org:device:dial:1');
    }

    public function filter($results = array())
    {
        if(is_array($results)){
            foreach($results as $usn=>$device){
                if($device['st'] !== 'urn:dial-multiscreen-org:device:dial:1'){
                    unset($results[$usn]);
                }
            }
        }
        return $results;
    }

}
