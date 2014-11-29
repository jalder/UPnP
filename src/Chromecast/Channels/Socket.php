<?php

namespace jalder\Upnp\Chromecast\Channels;

require_once(dirname(__FILE__).'/../pb_proto_message.php');

class Socket
{
    private $sessionId;
    private $mediaSessionId;
    private $socket;
    private $messages;
    private $replies;
    private $ns_heartbeat = 'urn:x-cast:com.google.cast.tp.heartbeat';
    private $ns_media = 'urn:x-cast:com.google.cast.media';
    private $ns_connect = 'urn:x-cast:com.google.cast.tp.connection';
    private $mode = 'die';
    private $sourceId = 'sender-0';
    private $destinationId = 'receiver-0';
    private $lastMessageStatus = 'open';
    private $messageQueue = array();
    private $replyQueue = array();
    private $appId;

    public function __construct($host = '', $mode = 'die')
    {
        if($host !== ''){
            //$this->host = $host;
            $context = stream_context_create(); //consider removing
            $this->socket = stream_socket_client($host, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            if($errno){
                die($errstr);
            }
            //set up defaults
            $this->message = new \CastMessage();  //protobuf for outgoing
            $this->replies = new \CastMessage();  //protobuf for incoming
            $this->message->setProtocolVersion('CASTV2_1_0');  //0
            $this->message->setSourceId($this->sourceId);
            $this->message->setDestinationId($this->destinationId);
            $this->message->setPayloadType('STRING');  //0
            //self::connect($host);
        }
        $this->mode = $mode;
    }

    /**
     * sets the socket mode.
     * modes available
     * die: close socket stream after command success
     * daemon: continue running until Exception
     */
    public function setMode($mode = 'die')
    {
        $this->mode = $mode;
    }

    public function connect($server, $url = '', $options = array())
    {
        $context = stream_context_create(); //consider removing
        $this->socket = $socket = stream_socket_client($server, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

        $source_id = 'sender-0';
        $destination_id = 'receiver-0';
        $namespace = 'urn:x-cast:com.google.cast.tp.connection';
        $payload_utf8 = json_encode(array('type'=>'CONNECT'));

        $this->message = $message = new \CastMessage();  //protobuf for outgoing
        $this->replies = $replies = new \CastMessage();  //protobuf for incoming
        $message->setNamespace($namespace);
        $message->setProtocolVersion('CASTV2_1_0');  //0
        $message->setSourceId($source_id);
        $message->setDestinationId($destination_id);
        $message->setPayloadType('STRING');  //0
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
        while (!feof($socket) && ($this->mode === 'daemon' || ($this->mode !== 'daemon' && $this->lastMessageStatus !== 'complete')))
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
                        self::addReply($replies->getPayloadUtf8());
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
                                    $this->lastMessageStatus = 'complete';
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
                //if(isset($appId)){
                //    self::status($message, $socket, $appId, 'urn:x-cast:com.google.cast.media');
                //}    
                self::ping();
                $heartbeat = time();
            }
            if(date('U') > $now + 30){
                if(isset($appId)){
                    //self::close($message, $socket, $appId);
                }
                //self::close($message, $socket);
                die('ran out of time');
            }
            self::checkReply();
        }
        fclose($socket);
    }

    public function execute($wait_on = 'MEDIA_STATUS')
    {
        $payload_utf8 = json_encode(array('type'=>'CONNECT'));
        $this->message->setNamespace($this->ns_connect);
        $this->message->setProtocolVersion('CASTV2_1_0');  //0
        $this->message->setSourceId($this->sourceId);
        $this->message->setDestinationId($this->destinationId);
        $this->message->setPayloadType('STRING');  //0
        //$this->message->setPayloadUtf8($payload_utf8);
        self::init($this->message, $this->socket);
        $now = date('U');
        $heartbeat = $now;
        $status = false;
        $msg_length = false;
        $msg = ''; 
        $takeaction = false;
        $loadit = true;
        $closeit = false;
        while (!feof($this->socket) && ($this->mode === 'daemon' || ($this->mode !== 'daemon' && $this->lastMessageStatus !== 'complete')))
        {

            if(!is_array($msg_length)){
                $msg = $msg.fread($this->socket, 1);
                $hex = bin2hex($msg);
                if(strlen($msg) == 4){
           
                    $msg_length = unpack('N', $msg);
                    $msg = '';
                }
            }
            if(is_array($msg_length)){
                $length = $msg_length[1];
                $msg = $msg.fread($this->socket, 1);
                if(strlen($msg) === $length){
                    $takeaction = true;
                    $msg_length = 0;
                    $length = 0;
                    try {
                        $this->replies->parseFromString($msg);
                        self::addReply($this->replies->getPayloadUtf8());
                        $this->replies->dump();
                    } catch (\Exception $ex) {
                        //die('Parse error: ' . $e->getMessage());
                    }

                    $msg = '';
                }

                if($this->replies->getPayloadUtf8()){
                    if($payload = json_decode($this->replies->getPayloadUtf8())){
                        var_dump($payload);
                        if(isset($payload->type)){
                            if($payload->type == 'PING' && $this->replies->getDestinationId()!='receiver-0'){
                                self::pong($this->message, $this->socket);
                            }
                            if($payload->type === 'RECEIVER_STATUS' && isset($payload->status->applications[0])){
                                var_dump('obtaining your app-id');
                                $appId = $payload->status->applications[0]->transportId;
                                $this->sessionId = $payload->status->applications[0]->sessionId;
                                var_dump($appId);
                                if($loadit){
                                    self::init($this->message, $this->socket, $appId);
                                    $loadit = false;
                                }
                            }
                            if($payload->type === 'MEDIA_STATUS'){
                                var_dump('obtaining your media session id');
                                $this->appId = $appId;
                                var_dump($payload);
                                if(isset($payload->status[0]->mediaSessionId) && $wait_on === 'MEDIA_STATUS')
                                {
                                    $this->mediaSessionId = $payload->status[0]->mediaSessionId;
                                    self::writeQueue();
                                }
                            }
                        }
                    }
                }
                else{
                    $payload = array();
                }
                
            }
            if((time() - $heartbeat) > 4){
                if(isset($appId)){
                    self::status($this->message, $this->socket, $appId, 'urn:x-cast:com.google.cast.media');
                }
                self::status($this->message,$this->socket);    
                self::ping();
                $heartbeat = time();
            }
            if(date('U') > $now + 30){
                die('execute ran out of time');
            }
            //self::checkReply();
        }
        fclose($this->socket);
    }


    private function writeQueue()
    {
        //if(!$this->socket){
        //    $this->socket = self::connect($this->host);
        //}
        //$this->socket = self::connect($this->host);
        var_dump('writing out message queue to socket');
        var_dump($this->messageQueue);
        foreach($this->messageQueue as $id=>$message){
            if(isset($this->mediaSessionId)){
                $message['mediaSessionId'] = $this->mediaSessionId;
                $this->message->setNamespace($this->ns_media);
                $this->message->setDestinationId($this->appId);
                //$this->message->setPayloadUtf8(json_encode($message));
                self::writeMessage($message);
                unset($this->messageQueue[$id]);
            }
            //$packed = $this->message->serializeToString();
            //$length = pack('N',strlen($packed));
            //$this->message->dump();
            //fwrite($this->socket, $length.$packed);
        }
    }

    private function writeMessage($message)
    {
        $this->message->setPayloadUtf8(json_encode($message));
        $packed = $this->message->serializeToString();
        $length = pack('N',strlen($packed));
        $this->message->dump();
        fwrite($this->socket, $length.$packed);
 
    }

    public function addMessage($message)
    {
        $this->messageQueue[] = $message;
        //if(isset($message['mediaSessionId'])){
            //$message['mediaSessionId'] = $this->mediaSessionId;
            //$this->message->setNamespace($ns_media);
            //$this->message->setPayloadUtf8(json_encode($message));
            //self::writeMessage();
            self::execute('MEDIA_STATUS');
        //}
    }

    private function addReply($reply)
    {
        $this->replyQueue[] = json_decode($reply);
    }

    private function checkReply()
    {
        //check reply, process queue accordingly
        foreach($this->replyQueue as $key=>$reply){
            unset($this->replyQueue[$key]); //replace with another method to handles queue cleaning
            var_dump($reply);
        }
    }

    public function ping()
    {
        $this->message->setDestinationId('receiver-0');
        $this->message->setNamespace($this->ns_heartbeat);
        $this->message->setPayloadUtf8('{"type": "PING"}');
        $this->writeMessage(array('type'=>'PING'));
    }

    public function pong($message, $socket)
    {
        $this->message->setDestinationId('receiver-0');
        $this->message->setNamespace($this->ns_heartbeat);
        $this->message->setPayloadUtf8('{"type": "PONG"}');
        $this->writeMessage(array('type'=>'PONG'));
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
        var_dump('attempting connection');
        $this->message->setDestinationId($destination);
        $this->message->setNamespace('urn:x-cast:com.google.cast.tp.connection');
        $this->message->setPayloadUtf8('{"type":"CONNECT"}');
        $packed = $this->message->serializeToString();
        $length = pack('N',strlen($packed));
        $this->message->dump();
        fwrite($this->socket, $length.$packed);
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
        $this->message->setNamespace($nm);
        $this->message->setDestinationId($destination);
        switch($nm){
            case 'urn:x-cast:com.google.cast.media':
                $this->message->setPayloadUtf8('{"type":"GET_STATUS","requestId":0,"mediaSessionId":"'.$this->mediaSessionId.'"}');
            break;
        }   
        $this->writeMessage(array('type'=>'GET_STATUS', 'requestId'=>12));
    }

}
