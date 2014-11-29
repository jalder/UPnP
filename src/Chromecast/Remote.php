<?php

namespace jalder\Upnp\Chromecast;

class Remote
{
    private $host = "";
    private $message;
    private $socket;
    private $channel;
    private $mediaSessionId;
    private $destinationId;

    public function __construct($device)
    {
        if(is_array($device)){
            if(isset($device['description']['URLBase'])){
                $baseUrl = $device['description']['URLBase'];
                $details = parse_url($baseUrl);
                if($details['host']){
                    $this->host = 'tls://'.$details['host'].':8009';
                }
            }
        }
        if($this->host === ""){
            return false; //host not set, consider throwing exception here
        }
        $this->channel = new Channels\Socket($this->host, 'die');
    }

    public function play($url = "", $autoplay = true, $position = false)
    {
        if($url !== ""){
            self::load($url, $autoplay, $position);
        }
        else{
            self::unPause();
        }
    }
    
    public function unPause()
    {
        $message = array(
            'mediaSessionId'=>$this->mediaSessionId,
            'requestId'=>1,
            'type'=>'PLAY'
        );
        $this->channel->addMessage($this->destinationId, $message);
    }

    public function pause()
    {
        $message = array(
            'mediaSessionId'=>$this->mediaSessionId,
            'requestId'=>1,
            'type'=>'PAUSE'
        );
        $this->channel->addMessage($message);
 
    }

    public function load($url, $autoplay = true, $position = false)
    {
        $this->channel->connect($this->host, $url);
    }

    public function stop()
    {

    }

    public function seek($time, $autoplay = true)
    {

    }

    public function getStatus()
    {

    }

    public function setVolume($level)
    {

    }

    public function getHost()
    {
        return $this->host;
    }
}
