<?php

namespace jalder\Upnp\Roku;

class Remote
{
    private $location;
    private $channel;

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
        $this->channel = new Channels\Curl($this->location);
    }

    public function home()
    {
        return $this->channel->addMessage('keypress/home');
    }

    public function back()
    {
        return $this->channel->addMessage('keypress/back');
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
        return $this->channel->addMessage('keypress/'.$dir);
    }

    public function ok()
    {
        return $this->channel->addMessage('keypress/select');
    }

    public function options()
    {
        return $this->channel->addMessage('keypress/info');
    }

    public function rewind()
    {
        return $this->channel->addMessage('keypress/rev');
    }

    public function fforward()
    {
        return $this->channel->addMessage('keypress/fwd');
    }

    public function pause()
    {
        return $this->channel->addMessage('keypress/play');
    }

    public function instantReplay()
    {
        return $this->channel->addMessage('keypress/instantreplay');
    }

    public function play()
    {
        return $this->channel->addMessage('keypress/play');
    }

    public function type($query)
    {
        foreach(str_split($query) as $char){
            $this->channel->addMessage('keypress/Lit_'.urlencode($char));
        }
        return true;
    }

    /**
     * to not confuse with communication channels, consider renaming to getApplications
     *
     */
    public function getChannels()
    {
        $response = $this->channel->addMessage('query/apps', false);
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
        return $this->channel->addMessage('launch/'.$channel_id.'?'.http_build_query($parameters));
    }

    public function getLocation()
    {
        return $this->location;
    }
}
