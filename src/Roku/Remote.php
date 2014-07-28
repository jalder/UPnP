<?php

namespace jalder\Upnp\Roku;

class Remote
{
    private $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function ok()
    {
        return $this->curl('keypress/select');
    }

    public function getChannels()
    {
        $response = $this->curl('query/apps', false);
        $xml = simplexml_load_string($response);
        $channels = array();
        foreach($xml->app as $app){
            $app_id = $app->attributes()->id;
            $channels[(string)$app_id] = array(
                'name' => (string)$app,
                'launch_url' => $this->location.'launch/'.(string)$app_id, //url to post to for launching channel
                'icon_url' => $this->location.'query/icon/'.(string)$app_id, //url containing image src for channel icon
            );
        }
        return $channels;
    }

    private function curl($request, $post = true)
    {
        $url = $this->location.$request;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        if($post){
            curl_setopt($ch,CURLOPT_POST,1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}
