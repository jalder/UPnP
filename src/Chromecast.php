<?php

namespace jalder\Upnp;

class Chromecast extends Core
{

    public function discover()
    {
        $devices = parent::search('urn:dial-multiscreen-org:device:dial:1');
        foreach($devices as $k=>$d){
            //if strpos google or chromecast keep
            if($d['description']['device']['modelName'] !== 'Eureka Dongle'){
                unset($devices[$k]);
            }
        }
        return $devices;
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
