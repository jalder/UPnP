<?php

namespace jalder\Upnp\Roku\Channels;

class Curl
{

    private $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function addMessage($message, $post = true, $params = array(), $auth = array())
    {
        return $this->curl($message, $post, $params, $auth);
    }


    private function curl($request, $post = true, $params = array(), $auth = array())
    {
        $url = $this->location.$request;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        if($post){
            curl_setopt($ch, CURLOPT_POST, true);
            if($count = count($params)){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
            else{
                $count = 1;
            }
            if(count($auth)){
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                curl_setopt($ch, CURLOPT_USERPWD, $auth['user'].':'.$auth['pass']);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}
