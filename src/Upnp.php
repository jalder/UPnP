<?php
/**
 * @author Jalder
 * Upnp class for interacting with UPnP network devices using PHP socket connections
 *
 * Derived from @author Morten Hekkvang <artheus@github>
 *
 */

namespace Jalder\Upnp;

class Upnp {

    public function __construct()
    {

    }


    //this will open socket and get upnp list
    public function alive()
    {
        $core = new Core();
        $results = $core->search();
        return (bool)count($results);
    }

}

