<?php

namespace jalder\Upnp;

class Roku extends Core
{

    public function discover()
    {
        return parent::search('roku:ecp');
    }

    public function filter($results = array())
    {
         if(is_array($results)){
            foreach($results as $usn=>$device){
                if($device['st'] !== 'roku:ecp'){
                    unset($results[$usn]);
                }
            }
        }
        return $results;
       
    }

}
