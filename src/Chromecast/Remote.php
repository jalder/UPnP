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

    public function __construct($device, $application, $channel = 'socket')
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

        $this->application = $application;
        
        if($this->host === ""){
            //return false; //host not set, consider throwing exception here
        }

        switch($channel)
        {
            case 'socket':
                $this->channel = new Channels\Socket($this->host, 'die');
                break;
            case 'sqlite':
                $this->channel = new Channels\Sqlite();
                break;
            default:
                $this->channel = new Channels\Socket($this->host, 'die');
                break;
        }
    }

    public function play($url = "", $autoplay = true, $position = false)
    {
        if($url !== ""){
            $this->launch();
            $this->load($url, $autoplay, $position);
        }
        else{
            $this->unPause();
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

    public function load($url, $autoplay = true, $contentType = 'video/mp4', $streamType = 'BUFFERED', $position = false)
    {
        $media_params = array(
            'contentId'=>$url,
            'contentType'=>$contentType,
            'streamType'=>$streamType
        );
        $message = array(
            'requestId'=>1,
            'type'=>'LOAD',
            'media'=>$media_params,
            'autoplay'=>$autoplay
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

    public function seek($time = 0.0, $resumeState = 'PLAYBACK_START')
    {
        $message = array(
            'mediaSessionId'=>$this->mediaSessionId,
            'requestId'=>1,
            'type'=>'SEEK',
            'resumeState'=>$resumeState, //or PLAYBACK_PAUSE
            'currentTime'=>$time
        );
        $this->channel->addMessage($message);
    }

    //should we do receiver or media status here? perhaps move receiver portion to Chromecast class?
    public function getStatus()
    {
        //$status = $this->channel->getMessages();
        //var_dump($status);
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

