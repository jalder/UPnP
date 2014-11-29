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
    private $application;

    public function __construct($device, $application = '')
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
        if($application !== ''){
            $this->application = $application;
        }
        if($this->host === ""){
            //return false; //host not set, consider throwing exception here
        }
        $this->channel = new Channels\Socket($this->host, 'die');
    }

    public function play($url = "", $autoplay = true, $position = false)
    {
        if($url !== ""){
            self::launch();
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
        $this->channel->addMessage($message);
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
        //$this->channel->connect($this->host, $url);
        $media_params = array(
            'contentId'=>$url,
            'contentType'=>'video/mp4',
            'streamType'=>'BUFFERED'
        );
        $message = array(
            'requestId'=>1,
            'type'=>'LOAD',
            'media'=>$media_params,
            'autoplay'=>true
        );
        $this->channel->addMessage($message);
    }


    public function launch()
    {
        $message = array(
            'requestId'=>1,
            'type'=>'LAUNCH',
            'appId'=>$this->application->getAppId()
        );
        $this->channel->addMessage($message, false);
    }

    public function stop()
    {
        $message = array(
            'mediaSessionId'=>$this->mediaSessionId,
            'requestId'=>1,
            'type'=>'STOP'
        );
        $this->channel->addMessage($message);
 
    }

    public function seek($time = 0.0)
    {
        $message = array(
            'mediaSessionId'=>$this->mediaSessionId,
            'requestId'=>1,
            'type'=>'SEEK',
            'resumeState'=>'PLAYBACK_START', //or PLAYBACK_PAUSE
            'currentTime'=>$time, //double, seconds from start
        );
        $this->channel->addMessage($message);
    }

    //should we do receiver or media status here? perhaps move receiver portion to Chromecast class?
    public function getStatus()
    {

    }

    //should we do receiver or media volume here? perhaps move receiver portion to Chromecast class?
    public function setVolume($level)
    {

    }

    public function getHost()
    {
        return $this->host;
    }
}
