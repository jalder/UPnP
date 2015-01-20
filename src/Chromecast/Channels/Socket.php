<?php
/**
 * Socket is a special Channel in that it not only implements Channel, but it also works with other Channels to communicate with the Chromecast
 *
 */

namespace jalder\Upnp\Chromecast\Channels;

require_once(dirname(__FILE__).'/../pb_proto_message.php');

class Socket implements Channel
{
    protected $sessionId;
    protected $mediaSessionId;
    private $socket;
    public $messages;
    public $replies;
    protected $ns_heartbeat = 'urn:x-cast:com.google.cast.tp.heartbeat';
    protected $ns_media = 'urn:x-cast:com.google.cast.media';
    protected $ns_connect = 'urn:x-cast:com.google.cast.tp.connection';
    protected $ns_receiver = 'urn:x-cast:com.google.cast.receiver';
    protected $mode = 'die';
    protected $sourceId = 'sender-0';
    protected $destinationId = 'receiver-0';
    private $lastMessageStatus = 'open';
    protected $messageQueue = array();
    protected $replyQueue = array();
    protected $appId;
    protected $verbosity;
    protected $channel;
    protected $receiverStatus;
    protected $mediaStatus;

    public function __construct($host = '', $mode = 'die', $verbosity = 0, $channel = 'socket')
    {
        if($host !== ''){
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
        }
        $this->mode = $mode;
        $this->verbosity = $verbosity;
        $this->setChannel($channel);
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

    public function setChannel($channel = 'socket')
    {
        switch($channel){
            case 'sqlite':
                $this->channel = new Sqlite();
                break;
            default:
                $this->channel = $channel;
                break;
        }
    }

    public function execute($wait_on = 'MEDIA_STATUS')
    {
        $this->message->setNamespace($this->ns_connect);
        $this->message->setProtocolVersion('CASTV2_1_0');  //0
        $this->message->setSourceId($this->sourceId);
        $this->message->setDestinationId($this->destinationId);
        $this->message->setPayloadType('STRING');  //0
        $this->init();
        $now = date('U');
        $heartbeat = $now;
        $status = false;
        $msg_length = false;
        $msg = ''; 
        $takeaction = false;
        $loadit = true;
        $closeit = false;

        if($this->socket){
            $this->status();
            while (!feof($this->socket) && ($this->mode === 'daemon' || ($this->mode !== 'daemon' && $this->lastMessageStatus !== 'complete')))
            {
                if(!is_array($msg_length)){
                    $msg = $msg.fread($this->socket, 1);
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
                            $this->addReply($this->replies->getPayloadUtf8());
                            //$this->replies->dump();
                        } catch (\Exception $ex) {
                            die('Parse error: ' . $e->getMessage());
                        }
                        $msg = '';
                    }

                    if($this->replies->getPayloadUtf8()){
                        if($payload = json_decode($this->replies->getPayloadUtf8())){
                            if($this->verbosity > 0){
                                //var_dump($payload);
                            }
                            if(isset($payload->type)){
                                if($payload->type === 'PING' && $this->replies->getDestinationId() !== 'receiver-0'){
                                    $this->pong();
                                }
                                if($payload->type === 'RECEIVER_STATUS' && isset($payload->status->applications[0])){
                                    $this->appId = $payload->status->applications[0]->transportId;
                                    $this->sessionId = $payload->status->applications[0]->sessionId;
                                    if($loadit && $payload->status->applications[0]->appId === 'CC1AD845'){
                                        $this->init($this->appId);
                                        $this->status($this->appId, 'urn:x-cast:com.google.cast.media');
                                        $loadit = false;
                                        $this->writeQueue('RECEIVER_STATUS');
                                        $this->lastMessage = 'complete';
                                    }
                                    if($payload->status->applications[0]->appId !== 'CC1AD845'){
                                        $this->writeQueue('CONNECT');
                                    }
                                    $this->receiverStatus = $payload;
                                }
                                if($payload->type === 'MEDIA_STATUS'){
                                    if(isset($payload->status[0]->mediaSessionId))
                                    {
                                        $this->mediaSessionId = $payload->status[0]->mediaSessionId;
                                        $this->writeQueue('MEDIA_STATUS');
                                    }
                                    $this->mediaStatus = $payload;
                                }
                                if($payload->type === 'CLOSE'){
                                    die('receiver kicked us out');
                                }
                            }
                        }
                    }
                    else{
                        $payload = array();
                    }
                    
                }
                if((date('U') - $heartbeat) > 4){
                    $this->ping();
                    $heartbeat = date('U');
                    //var_dump($this->getStatus());
                }
                if((date('U') > ($now + 30)) && $this->mode !== 'daemon'){
                    die('execute ran out of time');
                }
                $this->checkReply();
                //if(isset($this->appId)){
                $this->getQueue();
                //}
            }
            fclose($this->socket);
        }
    }

    private function getQueue()
    {

        if($this->channel !== 'socket'){
            $this->messageQueue = $this->channel->getMessages();
            if(count($this->messageQueue)){
                var_dump($this->messageQueue);
            }
            foreach($this->messageQueue as $queue => $messages){
                $this->writeQueue($queue);
            }
        }

    }

    public function writeQueue($queue = 'MEDIA_STATUS')
    {
        if($this->verbosity > 0){
            //var_dump('writing out message queue to socket');
            //var_dump($this->messageQueue);
        }
        if(isset($this->messageQueue[$queue])){
            foreach($this->messageQueue[$queue] as $id=>$message){
                switch($queue){
                    case 'CONNECT':
                        $this->message->setNamespace($this->ns_receiver);
                        unset($this->messageQueue[$queue][$id]);
                        $this->writeMessage($message);
                        break;
                    case 'RECEIVER_STATUS':
                        $this->message->setNamespace($this->ns_media);
                        $this->message->setDestinationId($this->appId);
                        unset($this->messageQueue[$queue][$id]);
                        $this->writeMessage($message);
                        break;
                    case 'MEDIA_STATUS':
                        if(isset($this->appId) && isset($this->mediaSessionId)){
                            $message['mediaSessionId'] = $this->mediaSessionId;
                            $this->message->setNamespace($this->ns_media);
                            $this->message->setDestinationId($this->appId);
                            unset($this->messageQueue[$queue][$id]);
                            $this->writeMessage($message);
                        }
                        break;
                }
                //$this->writeMessage($message);
                //unset($this->messageQueue[$queue][$id]);
            }
        }
        if($queue == 'all'){
            foreach($this->messageQueue as $queueType){
                $safe_to_run = false;
                switch($queueType){
                    case 'CONNECT':
                        $safe_to_run = true;
                        $this->message->setNamespace($this->ns_receiver);
                        break;
                    case 'RECEIVER_STATUS':
                        $this->message->setNamespace($this->ns_media);
                        if($this->appId){
                            $this->message->setDestinationId($this->appId);
                            $safe_to_run = true;
                        }
                        break;
                    case 'MEDIA_STATUS':
                        $this->message->setNamespace($this->ns_media); 
                        if(isset($this->appId) && isset($this->mediaSessionId)){ 
                            $this->message->setDestinationId($this->appId);
                            foreach($this->messageQueue[$queueType] as &$message){
                                $message['mediaSessionId'] = $this->mediaSessionId;
                            }
                            $safe_to_run = true;
                        }
                        break;
                }
                if($safe_to_run){
                    foreach($this->messageQueue[$queueType] as $id=>$message){
                        unset($this->messageQueue[$queueType][$id]);
                        $this->writeMessage($message);
                    }
                }
                else{
                    $this->status();
                }
            }
        }
    }

    public function buildPayload($message)
    {
        switch($message['type']){
            case 'LAUNCH':
                $this->message->setNamespace($this->ns_receiver);
                break;
            case 'LOAD':
                $this->message->setNamespace($this->ns_media);
                break;
            default:
                break;
        }
        $this->message->setPayloadUtf8(json_encode($message));
        $packed = $this->message->serializeToString();
        $length = pack('N',strlen($packed));
        return $length.$packed;
    }

    protected function writeMessage($message)
    {
        $this->message->setPayloadUtf8(json_encode($message));
        $packed = $this->message->serializeToString();
        $length = pack('N',strlen($packed));
        if($this->verbosity > 0 && $message['type'] !== 'PING' && $message['type'] !== 'PONG'){
            $this->message->dump();
        }
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
            $this->execute();
        }
    }

    public function getMessages()
    {

    }

    private function addReply($reply)
    {
        $this->replyQueue[] = json_decode($reply);
        if($this->channel !== 'socket'){
            $this->channel->addReply(json_decode($reply));
        }
    }

    private function checkReply()
    {
        //check reply, process queue accordingly
        foreach($this->replyQueue as $key=>$reply){
            //unset($this->replyQueue[$key]); //replace with another method to handles queue cleaning
            //var_dump($reply);
            if($reply->type === 'RECEIVER_STATUS'){
                $this->receiverStatus = $reply;
                $this->writeReply($reply);
                unset($this->replyQueue[$key]);
            }
            if($reply->type === 'MEDIA_STATUS'){
                $this->mediaStatus = $reply;
                $this->writeReply($reply);
                unset($this->replyQueue[$key]);
            }
            if($reply->type === 'PING' || $reply->type === 'PONG'){
                unset($this->replyQueue[$key]);
            }
            //die();
        }
    }

    public function handleReply()
    {
        $status = '';
        if($this->replies->getPayloadUtf8()){
            if($payload = json_decode($this->replies->getPayloadUtf8())){
                if($this->verbosity > 0){
                    var_dump($payload);
                }
                if(isset($payload->type)){
                    if($payload->type === 'PING' && $this->replies->getDestinationId() !== 'receiver-0'){
                        $this->pong();
                        $status = 'ping';
                    }
                    if($payload->type === 'RECEIVER_STATUS' && isset($payload->status->applications[0])){
                        $this->appId = $payload->status->applications[0]->transportId;
                        $this->sessionId = $payload->status->applications[0]->sessionId;
                        $status = 'receiver_status';
                        if($payload->status->applications[0]->appId === 'CC1AD845'){
                            $this->init($this->appId);
                            $this->status($this->appId, 'urn:x-cast:com.google.cast.media');
                            //$loadit = false;
                            $this->writeQueue('RECEIVER_STATUS');
                            $this->lastMessage = 'complete';
                            //if ready to cast state, set status as readyPlaylistNext
                            if($payload->status->applications[0]->statusText === "Ready To Cast"){
                                $status = 'readyPlaylistNext';
                            }
                        }
                        if($payload->status->applications[0]->appId !== 'CC1AD845'){
                            $this->writeQueue('CONNECT');
                            $status = 'appNotRunning';
                        }
                        $this->receiverStatus = $payload;
                    }
                    if($payload->type === 'MEDIA_STATUS'){
                        if(isset($payload->status[0]->mediaSessionId))
                        {
                            $this->mediaSessionId = $payload->status[0]->mediaSessionId;
                            $this->writeQueue('MEDIA_STATUS');
                            if($payload->status[0]->playerState === 'IDLE' || count($payload->status)===0){
                                $status = 'readyPlaylistNext';
                            }
                        }
                        $this->mediaStatus = $payload;
                    }
                    if($payload->type === 'CLOSE'){
                        //die('receiver kicked us out');
                        $this->receiverStatus = 'close';
                        $this->mediaStatus = 'close';
                        $status = 'close';
                    }
                }
            }
        }
        else{
            $payload = array();
        }
        return $status;
    }

    public function writeReply($reply)
    {
        var_dump($reply);
    }

    public function getStatus($type = 'RECEIVER_STATUS')
    {
        switch($type)
        {
            case 'RECEIVER_STATUS':
                if($this->receiverStatus === null){
                    $this->status();
                    //while($this->receiverStatus === null){
                    //}
                }
                return $this->receiverStatus;
            break;
            case 'MEDIA_STATUS':
                return $this->mediaStatus;
            break;
        }
    }

    public function ping()
    {
        $this->message->setDestinationId($this->destinationId);
        $this->message->setNamespace($this->ns_heartbeat);
        $this->writeMessage(['type'=>'PING']);
    }

    public function pong()
    {
        $this->message->setDestinationId($this->destinationId);
        $this->message->setNamespace($this->ns_heartbeat);
        $this->writeMessage(['type'=>'PONG']);
    }

    public function init($destination = 'receiver-0')
    {
        $this->message->setDestinationId($destination);
        $this->message->setNamespace($this->ns_connect);
        $this->writeMessage(['type'=>'CONNECT']);
    }

    public function close($destination = 'receiver-0')
    {
        $this->message->setDestinationId($destination);
        $this->message->setNamespace($this->ns_connect);
        $this->writeMessage(['type'=>'CLOSE']);
    }

    public function status($destination = 'receiver-0', $nm = 'urn:x-cast:com.google.cast.receiver')
    {
        $this->message->setNamespace($nm);
        $this->message->setDestinationId($destination);
        switch($nm){
            case 'urn:x-cast:com.google.cast.media':
                $payload = ['type'=>'GET_STATUS', 'requestId'=>1, 'mediaSessionId'=>$this->mediaSessionId]; 
                break;
            default:
                $payload = ['type'=>'GET_STATUS', 'requestId'=>1];
                break;
        }   
        $this->writeMessage($payload);
    }
}
