<?php

namespace jalder\Upnp\Dial;
use jalder\Upnp\Core;


class Remote
{
    private $upnp;
    private $appurl;
    public function __construct($appurl)
	{
        $this->upnp = new Core();
        $this->appurl = $appurl;
	}

    public function loadApp($app)
    {
        
        $ch = curl_init();
        //curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_URL, $this->appurl.$app );
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, [] );
        $response = curl_exec( $ch );
        curl_close( $ch );
        var_dump($response);

    }

}
