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
    private $ns_receiver = 'urn:x-cast:com.google.cast.receiver';
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
        self::status($this->message,$this->socket);
        $now = date('U');
        $heartbeat = $now;
        $status = false;
        $msg_length = false;
        $msg = ''; 
        $takeaction = false;
        $loadit = true;
        $closeit = false;
        //self::writeQueue('CONNECT');
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
                                //if(($appId = $payload->status->applications[0]->transportId) !== null){
                                    $this->appId = $appId = $payload->status->applications[0]->transportId;
                                    $this->sessionId = $payload->status->applications[0]->sessionId;
                                    var_dump($appId);
                                //}
                                if($loadit && $payload->status->applications[0]->appId === 'CC1AD845'){
                                    self::init($this->message, $this->socket, $this->appId);
                                    self::status($this->message, $this->socket, $appId, 'urn:x-cast:com.google.cast.media');
                                    $loadit = false;
                                    //self::writeQueue('RECEIVER_STATUS');
                                    self::writeQueue('RECEIVER_STATUS');
                                    $this->lastMessage = 'complete';
                                }
                                if($payload->status->applications[0]->appId !== 'CC1AD845'){
                                    self::writeQueue('CONNECT');
                                }
                            }
                            if($payload->type === 'MEDIA_STATUS'){
                                var_dump('obtaining your media session id');
                                $this->appId = $appId;
                                var_dump($payload);
                                if(isset($payload->status[0]->mediaSessionId))
                                {
                                    $this->mediaSessionId = $payload->status[0]->mediaSessionId;
                                    self::writeQueue('MEDIA_STATUS');
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
                    //self::status($this->message, $this->socket, $appId, 'urn:x-cast:com.google.cast.media');
                }
                //self::status($this->message,$this->socket);    
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


    private function writeQueue($queue = 'MEDIA_STATUS')
    {
        var_dump('writing out message queue to socket');
        var_dump($this->messageQueue);
        if(isset($this->messageQueue[$queue])){
            foreach($this->messageQueue[$queue] as $id=>$message){
                switch($queue){
                    case 'CONNECT':
                        $this->message->setNamespace($this->ns_receiver);
                        break;
                    case 'RECEIVER_STATUS':
                        $this->message->setNamespace($this->ns_media);
                        $this->message->setDestinationId($this->appId);
                        break;
                    case 'MEDIA_STATUS':
                        $message['mediaSessionId'] = $this->mediaSessionId;
                        $this->message->setNamespace($this->ns_media);
                        $this->message->setDestinationId($this->appId);
                        break;
                }
                self::writeMessage($message);
                unset($this->messageQueue[$queue][$id]);
            }
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

    public function addMessage($message, $execute = true)
    {
        if($message['type'] === 'LAUNCH'){
            $this->messageQueue['CONNECT'][] = $message;
        }
        else if($message['type'] === 'LOAD'){
            $this->messageQueue['RECEIVER_STATUS'][] = $message;
        }
        else{
            $this->messageQueue['MEDIA_STATUS'][] = $message;
        }
        if($execute){
            self::execute();
        }
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
        $this->writeMessage(array('type'=>'PING'));
    }

    public function pong($message, $socket)
    {
        $this->message->setDestinationId('receiver-0');
        $this->message->setNamespace($this->ns_heartbeat);
        $this->writeMessage(array('type'=>'PONG'));
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
