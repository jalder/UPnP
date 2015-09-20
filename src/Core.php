<?php

namespace jalder\Upnp;

class Core {

    private $user_agent;
    public $cache;

    public function __construct()
    {
        $this->user_agent = 'Roku/DVP-5.5 (025.05E00410A)';
        //$this->user_agent = 'Xbox';
    }
    
    public function search($st = 'ssdp:all', $mx = 2, $man = 'ssdp:discover', $from = null, $port = null, $sockTimout = '2')
    {
        $request = 'M-SEARCH * HTTP/1.1'."\r\n";
        $request .= 'HOST: 239.255.255.250:1900'."\r\n";
        $request .= 'MAN: "'.$man.'"'."\r\n";
        $request .= 'MX: '.$mx.''."\r\n";
        $request .= 'ST: '.$st.''."\r\n";
        $request .= 'USER-AGENT: '.$this->user_agent."\r\n";
        $request .= "\r\n";

        $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, true);
        socket_sendto($socket, $request, strlen($request), 0, '239.255.255.250', 1900);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sockTimout, 'usec'=>'0'));
        $response = array();
        do {
            $buf = null;
            socket_recvfrom($socket, $buf, 1024, MSG_WAITALL, $from, $port);
            if(!is_null($buf)){
                $data = $this->parseSearchResponse($buf);
                $response[$data['usn']] = $data;
            }
        } while(!is_null($buf));
        socket_close($socket);
        return $response;
    }

    private function parseSearchResponse($response)
    {
        //var_dump($response);
        $messages = explode("\r\n", $response);
        $parsedResponse = array();
        foreach( $messages as $row ) {
            if( stripos( $row, 'http' ) === 0 )
                $parsedResponse['http'] = $row;
            if( stripos( $row, 'cach' ) === 0 )
                $parsedResponse['cache-control'] = str_ireplace( 'cache-control: ', '', $row );
            if( stripos( $row, 'date') === 0 )
                $parsedResponse['date'] = str_ireplace( 'date: ', '', $row );
            if( stripos( $row, 'ext') === 0 )
                $parsedResponse['ext'] = str_ireplace( 'ext: ', '', $row );
            if( stripos( $row, 'loca') === 0 )
                $parsedResponse['location'] = str_ireplace( 'location: ', '', $row );
            if( stripos( $row, 'serv') === 0 )
                $parsedResponse['server'] = str_ireplace( 'server: ', '', $row );
            if( stripos( $row, 'st:') === 0 )
                $parsedResponse['st'] = str_ireplace( 'st: ', '', $row );
            if( stripos( $row, 'usn:') === 0 )
                $parsedResponse['usn'] = str_ireplace( 'usn: ', '', $row );
            if( stripos( $row, 'cont') === 0 )
                $parsedResponse['content-length'] = str_ireplace( 'content-length: ', '', $row );
        }
        $parsedResponse['description'] = $this->getDescription($parsedResponse['location']);
        return $parsedResponse;
    }
    
    public function getDescription($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        //var_dump($content);
        //$xml = new \DOMDocument();
        //$xml->load($url);
        //$xml->load($content);
        //$xml->read();
        //var_dump($xml);
        //if($xml->validate()){
            //var_dump($content); die();
            libxml_use_internal_errors(true); 
            $xml = simplexml_load_string($content);
            $json = json_encode($xml);
            $desc = (array)json_decode($json, true);
            curl_close($ch);
        //}
        //else{
            //die($url);
        //    return false;
        //}
        return $desc;
    }

    public function getHeader($url)
    {
//        var_dump($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
//        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        var_dump($httpCode);
        $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//        var_dump($content);
        $header = substr($content, 0, $size);
        curl_close($ch);
//        var_dump($header);
        $messages = explode("\r\n", $header);
        $parsed = [];
        foreach($messages as $m){
            //            var_dump($m);
            if(count(explode(':',$m))>1){
                list($param, $value) = explode(':',$m, 2);
                $parsed[$param] = $value;
            }
            else{
                $parsed[$m] = $m;
            }
        }
        $parsed['httpCode'] = $httpCode;
        return $parsed;
    }

    public function sendRequestToDevice($method, $arguments, $url, $type, $hostIp = '127.0.0.1', $hostPort = '80')
    {
        $body  ='<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        $body .='<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
        $body .='<s:Body>';
        $body .='<u:'.$method.' xmlns:u="urn:schemas-upnp-org:service:'.$type.':1">';
        foreach( $arguments as $arg=>$value ) {
            $body .='<'.$arg.'>'.$value.'</'.$arg.'>';
        }
        $body .='</u:'.$method.'>';
        $body .='</s:Body>';
        $body .='</s:Envelope>';
 
        $header = array(
            'SOAPAction: "urn:schemas-upnp-org:service:'.$type.':1#'.$method.'"',
            'Content-Type: text/xml; charset="utf-8"',
            'Host: '.$hostIp.':'.$hostPort,
            'Connection: close',
            'Accept-Language: en-us;q=1, en;q=0.5',
            'Accept-Encoding: gzip',
            'User-Agent: '.$this->user_agent, //fudge the user agent to get desired video format
            'Content-Length: ' . strlen($body),
        );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
        $response = curl_exec( $ch );
        curl_close( $ch );

        //var_dump($response);

        $doc = new \DOMDocument();
        $doc->loadXML($response);
        $result = $doc->getElementsByTagName('Result');
        if(is_object($result->item(0))){
            return $result->item(0)->nodeValue;
        }
        return false;

    }

    public function baseUrl($url)
    {
        $url = parse_url($url);
        return $url['scheme'].'://'.$url['host'].':'.$url['port'];
    }

    public function setUserAgent($agent)
    {
        $this->user_agent = $agent;
    }
}
