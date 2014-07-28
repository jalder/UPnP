<?php

namespace jalder\Upnp;

class Roku extends Core
{

    public function discover()
    {
        return parent::search('roku:ecp');
    }

}
