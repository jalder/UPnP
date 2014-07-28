<?php


namespace jalder\Upnp;

class Core {

    private $user_agent;

    public function __construct()
    {
        $this->user_agent = '"Mozilla/5.0 UPnP/1.1 Jalder Upnp/0.0.1"';
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
        socket_set_option($socket, 1, 6, true);
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
        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $desc = (array)json_decode($json, true);
        curl_close($ch);
        return $desc;
    }

}
