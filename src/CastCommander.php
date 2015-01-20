<?php

namespace jalder\Upnp;

class CastCommander extends Core
{
    public function discover()
    {
        $servers = parent::search('urn:schemas-jalder-com:service:CastCommander:1');
        return $servers;
    }

    public function filter($results = [])
    {
        if(is_array($results)){
            foreach($results as $usn=>$device){
                if($device['st'] !== 'urn:schemas-jalder-com:service:CastCommander:1'){
                    unset($results[$usn]);
                }
            }
        }
        return $results;
    }
}
