<?php

namespace jalder\Upnp\Roku;

class Remote
{
    private $location;

    public function __construct($location)
    {
        $this->location = $location;
        if(is_array($location)){
            if(isset($location['location'])){
                $this->location = $location['location'];
            }
            else{
                $last = array_pop($location);
                if(isset($last['location'])){
                     $this->location = $last['location'];
                }
            }
        }
    }

    public function home()
    {
        return $this->curl('keypress/home');
    }

    public function back()
    {
        return $this->curl('keypress/back');
    }

    public function dPad($dir = 'up')
    {
        switch($dir)
        {
            case 'up':
                $dir = 'up';
            break;
            case 'down':
                $dir = 'down';
            break;
            case 'left':
                $dir = 'left';
            break;
            case 'right':
                $dir = 'right';
            break;
            default:
                $dir = 'up';
            break;
        }
        return $this->curl('keypress/'.$dir);
    }

    public function ok()
    {
        return $this->curl('keypress/select');
    }

    public function options()
    {
        return $this->curl('keypress/info');
    }

    public function rewind()
    {
        return $this->curl('keypress/rev');
    }

    public function fforward()
    {
        return $this->curl('keypress/fwd');
    }

    public function pause()
    {
        return $this->curl('keypress/play');
    }

    public function play()
    {
        return $this->curl('keypress/play');
    }

    public function type($query)
    {
        foreach(str_split($query) as $char){
            $this->curl('keypress/Lit_'.urlencode($char));
        }
        return true;
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

    public function loadChannel($channel_id, $parameters = array())
    {
        return $this->curl('launch/'.$channel_id.'?'.http_build_query($parameters));
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

    public function getLocation()
    {
        return $this->location;
    }
}
