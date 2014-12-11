<?php

namespace jalder\Upnp\Roku;

class Developer
{

    private $location;
    private $channel;

    public function __construct($location)
    {
        $this->location = $location;
        $this->channel = new Channels\Curl($this->location);
    }

    public function pluginInstall($zip, $auth)
    {
        $params = ['mysubmit'=>'Install', 'archive'=>'@'.$zip];
        $response = $this->channel->addMessage('/plugin_install', $post = true, $params, $auth);
        var_dump($response);
    }
}
