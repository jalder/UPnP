<?php

namespace jalder\Upnp;

require(dirname(__FILE__).'/Chromecast/pb_proto_message.php');

class Chromecast extends Core
{

    public function discover()
    {
        return parent::search('urn:dial-multiscreen-org:device:dial:1');
    }

    public function filter($results = array())
    {
        if(is_array($results)){
            foreach($results as $usn=>$device){
                if($device['st'] !== 'urn:dial-multiscreen-org:device:dial:1'){
                    unset($results[$usn]);
                }
            }
        }
        return $results;
    }

    public function connect()
    {
        $context = stream_context_create(); 
        $socket = stream_socket_client('tls://192.168.1.102:8009', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
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
        self::launch($message, $socket);
        while (!feof($socket))
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
                        if(isset($payload->type)){
                            if($payload->type == 'PING' && $replies->getDestinationId()!='receiver-0'){
                                self::pong($message, $socket);
                            }
                            if($payload->type === 'RECEIVER_STATUS' && isset($payload->status->applications[0])){
                                var_dump('obtaining your app-id');
                                $appId = $payload->status->applications[0]->transportId;
                                var_dump($appId);
                                self::init($message, $socket, $appId);
                                if($loadit){
                                    $loadit = false;
                                    sleep(4);
                                    self::load($message, $socket, $appId);
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
            if(date('U') > $now + 20){
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
        $media_params['contentId'] = 'http://192.168.1.20:49152/content/media/object_id/6769/res_id/0/ext/file.mp4';
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
        $packed = $message->serializeToString();
        $length = pack('N',strlen($packed));
        $message->dump();
        fwrite($socket, $length.$packed);
    }
}
