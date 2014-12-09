<?php

namespace jalder\Upnp\Roku;

use jalder\Upnp\Roku;
use jalder\Upnp\Roku\Remote;

class Player
{

    private $remote;
    private $location;
    private $application;

    public function __construct($device, $application = 'simplevideoplayer')
    {
        $this->remote = new Remote($device);
        $this->location = $this->remote->getLocation();
        switch($application)
        {
            case 'simplevideoplayer':
                $this->application = new Applications\Simplevideoplayer();
                break;
            default:
                $this->application = $application;
                break;
        }
    }

    public function play($video)
    {
        if(is_array($video)){

            if(strpos($video['url'],'youtube')&&false){
                $url = parse_url($video['url']);
                parse_str($url['query'],$query);
                $arguments = array(
                    'v'=>$query['v']
                );
                foreach($this->remote->getChannels() as $id=>$ch){
                    if($ch['name'] == 'YouTube'){
                        $response = $this->remote->loadChannel($id, $arguments);
                    }
                }
            }
            else{
                $arguments = array(
                    'url'=>$video['url'],
                    'StreamFormat'=>$video['format'],
                    'srt'=>$video['subtitle_url'],
                    'title'=>$video['title'],
               
                ); 
                $response = $this->remote->loadChannel($this->application->getAppId(),$this->application->launchParams($arguments));
                $this->application->load();
            }    
            return $response;
        }
    }
}
