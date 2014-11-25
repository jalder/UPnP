<?php

namespace jalder\Upnp\Chromecast\Channels;

require_once(dirname(__FILE__).'/../pb_proto_message.php');

class Socket
{
    private $sessionId;
    private $mediaSessionId;
    private $socket;

    public function __construct()
    {

    }

    public function connect($server, $url = '', $options = array())
    {
        $context = stream_context_create(); 
        $socket = stream_socket_client($server, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        var_dump($socket);
        var_dump($errno);
        var_dump($errstr);

        $protocol_version = array('CASTV2_1_0'=> 0);
        $source_id = 'client-1112';
        $destination_id = 'receiver-0';
        $namespace = 'urn:x-cast:com.google.cast.tp.connection';
        $payload_utf8 = json_encode(array('type'=>'CONNECT'));

        $message = new \CastMessage();
        $replies = new \CastMessage();
        $message->setNamespace($namespace);
        $message->setProtocolVersion('CASTV2_1_0');
        $message->setSourceId($source_id);
        $message->setDestinationId($destination_id);
        $message->setPayloadType('STRING');
        $message->setPayloadUtf8($payload_utf8);
        self::init($message, $socket);
        $now = date('U');
        $heartbeat = $now;
        $status = false;
        $msg_length = false;
        $msg = ''; 
        $takeaction = false;
        $loadit = true;
        $closeit = false;
        self::launch($message, $socket);
        while (!feof($socket) && !$closeit)
        {

            if(!is_array($msg_length)){
                $msg = $msg.fread($socket, 1);
                $hex = bin2hex($msg);
                if(strlen($msg) == 4){
           
                    $msg_length = unpack('N', $msg);
                    $msg = '';
                }
            }
            if(is_array($msg_length)){
                $length = $msg_length[1];
                $msg = $msg.fread($socket, 1);
                if(strlen($msg) === $length){
                    $takeaction = true;
                    $msg_length = 0;
                    $length = 0;
                    try {
                        $replies->parseFromString($msg);
                        $replies->dump();
                    } catch (\Exception $ex) {
                        //die('Parse error: ' . $e->getMessage());
                    }

                    $msg = '';
                }
 

                if($replies->getPayloadUtf8()&&$takeaction){
                    if($payload = json_decode($replies->getPayloadUtf8())){
                        var_dump($payload);
                        if(isset($payload->type)){
                            if($payload->type == 'PING' && $replies->getDestinationId()!='receiver-0'){
                                self::pong($message, $socket);
                            }
                            if($payload->type === 'RECEIVER_STATUS' && isset($payload->status->applications[0])){
                                var_dump('obtaining your app-id');
                                //var_dump($payload->status->applications);
                                $appId = $payload->status->applications[0]->transportId;
                                $this->sessionId = $payload->status->applications[0]->sessionId;
                                var_dump($appId);
                                if($loadit){
                                    self::init($message, $socket, $appId);
                                    $loadit = false;
                                    //sleep(4);
                                    self::load($message, $socket, $appId, array('url'=>$url));
                                }
                            }
                            if($payload->type === 'MEDIA_STATUS'){
                                var_dump('obtaining your media session id');
                                $this->mediaSessionId = $payload->status[0]->mediaSessionId;
                                
                                if($payload->status[0]->playerState === 'PLAYING'){
                                    if(isset($appId)){
                                        //self::close($message, $socket, $appId);
                                    }
                                    //self::close($message, $socket);
                                    $closeit = true;
                                    var_dump('trying to close early, mission accomplished'); 
                                }
                            }
                        }
                    }
                    $takeaction = false;
                }
                else{
                    $payload = array();
                }
                
            }
            if((time() - $heartbeat) > 4){
                if(isset($appId)){
                    self::status($message, $socket, $appId, 'urn:x-cast:com.google.cast.media');
                }    
                self::ping($message, $socket);
                $heartbeat = time();
            }
            if(date('U') > $now + 30){
                if(isset($appId)){
                    self::close($message, $socket, $appId);
                }
                self::close($message, $socket);
                die('ran out of time');
            }
            
        }
        fclose($socket);
    }

    public function ping($message, $socket)
    {
        $message->setNamespace('urn:x-cast:com.google.cast.tp.heartbeat');
        $message->setPayloadUtf8('{"type": "PING"}');
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        fwrite($socket, $length.$packed);
    }

    public function pong($message, $socket)
    {

        $message->setNamespace('urn:x-cast:com.google.cast.tp.heartbeat');
        $message->setPayloadUtf8('{"type": "PONG"}');
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        fwrite($socket, $length.$packed);
    }

    public function launch($message, $socket, $url = "")
    {
        $message->setNamespace('urn:x-cast:com.google.cast.receiver');
        $message->setPayloadUtf8('{"type":"LAUNCH","appId":"CC1AD845","requestId":1}');
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        fwrite($socket, $length.$packed);
    }

    public function load($message, $socket, $destination, $params = array())
    {
        $message->setNamespace('urn:x-cast:com.google.cast.media');
        $message->setDestinationId($destination);
        $media_params = array(
            'contentId'=>'https://commondatastorage.googleapis.com/gtv-videos-bucket/big_buck_bunny_1080p.mp4',
            'contentType'=>'video/mp4',
            'streamType'=>'BUFFERED'
        );
        if(isset($params['url'])){
            $media_params['contentId'] = $params['url'];
        }
        $params = array(
            'requestId'=>1,
            'type'=>'LOAD',
            'media'=>$media_params,
            'autoplay'=>true
        );
        $message->setPayloadUtf8(json_encode($params));
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        fwrite($socket, $length.$packed);
        $message->dump();
    }

    public function youtube($message, $socket)
    {
        $message->setNamespace('urn:x-cast:com.google.cast.receiver');
        $message->setPayloadUtf8('{"type":"LAUNCH","appId":"YouTube","requestId":1}');
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        fwrite($socket, $length.$packed);
    }

    public function init($message, $socket, $destination = 'receiver-0')
    {
        var_dump('attempted connection');
        $message->setDestinationId($destination);
        $message->setNamespace('urn:x-cast:com.google.cast.tp.connection');
        $message->setPayloadUtf8('{"type":"CONNECT"}');
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        $message->dump();
        fwrite($socket, $length.$packed);
    }

    public function close($message, $socket, $destination = 'receiver-0')
    {
        $message->setDestinationId($destination);
        var_dump('attempted close connection');
        $message->setNamespace('urn:x-cast:com.google.cast.tp.connection');
        $message->setPayloadUtf8(json_encode(array('type'=>'CLOSE')));
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        $message->dump();
        fwrite($socket, $length.$packed);

    }

    public function status($message, $socket, $destination = 'receiver-0', $nm = 'urn:x-cast:com.google.cast.receiver')
    {
        $message->setNamespace($nm);
        $message->setDestinationId($destination);
        $message->setPayloadUtf8('{"type":"MEDIA_STATUS"}');
        switch($nm){
            case 'urn:x-cast:com.google.cast.media':
                $message->setPayloadUtf8('{"type":"GET_STATUS","requestId":0,"mediaSessionId":"'.$this->mediaSessionId.'"}');
            break;
        }   

        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        $message->dump();
        fwrite($socket, $length.$packed);
    }

}
