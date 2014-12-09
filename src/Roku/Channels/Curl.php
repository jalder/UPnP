<?php

namespace jalder\Upnp\Roku\Channels;

class Curl
{

    private $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function addMessage($message, $post = true)
    {
        return $this->curl($message, $post);
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