<?php

namespace jalder\Upnp\Roku;
use jalder\Upnp\Roku;
use jalder\Upnp\Roku\Remote;

class Player{

    private $remote;
    private $location;

    public function __construct($location)
    {
        $this->remote = new Remote($location);
        $this->location = $this->remote->getLocation();
    }

    public function play($video)
    {
        if(is_array($video)){

            if(strpos($video['url'],'youtube')){
                //var_dump($this->remote->getChannels());
                $url = parse_url($video['url']);
                parse_str($url['query'],$query);
                $arguments = array(
                    'v'=>$query['v']
                );
                var_dump(http_build_query($arguments));
                foreach($this->remote->getChannels() as $id=>$ch){
                    if($ch['name'] == 'YouTube'){
                        $response = $this->remote->loadChannel($id, $arguments);
                        var_dump($response.$id);
                    }
                }

                die();
            }

            $arguments = array(
                'url'=>$video['url'],
                'StreamFormat'=>$video['format'],
                'srt'=>$video['subtitle_url'],
                'title'=>$video['title'],
               
            );
            $response = $this->remote->loadChannel('dev',$arguments);
            return true;
        }
    }

}
