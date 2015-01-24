<?php

namespace jalder\Upnp;

class Dial extends Core
{

    public $registry = 'https://spreadsheets.google.com/feeds/list/0AuFksubOBK_7dGlmcHg3am5SVldPYzRmMEgwXy1vMHc/od6/public/basic'; // or ?alt=json

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

    public function getApplicationUrl($url)
    {
        $headers = $this->getHeader($url);
        //var_dump($headers);
        return trim($headers['Application-URL']);
    }

    public function getRegistry()
    {
        $reg = $this->getDescription($this->registry);
        //var_dump($reg);
        $registry = array();
        foreach($reg['entry'] as $e){
            list($fullauth,$fullapp) = explode(',', $e['content']);
            list($keyauth,$author) = explode(':',$fullauth);
            list($keyapp,$app) = explode(':',$fullapp);
            $registry[$e['content']] = [
                'content'=>$e['content'],
                'company'=>$e['title'],
                'author'=>trim($author),
                'app'=>trim($app)
            ];
        }
        return $registry;
    }

    public function getApp($appurl)
    {
        $headers = $this->getHeader($appurl);
        if($headers['httpCode'] === 404){
            return false;
        }
        //var_dump($headers);
        return $headers;
    }

    public static function findApps($location)
    {
        $applications = [];
        $dial = new Dial();
        $appurl = $dial->getApplicationUrl($location);
        $registry = $dial->getRegistry();
        foreach($registry as $r){
            if($dial->getApp($appurl.urlencode($r['app']))){
                $applications[] = $r;
            }
        }
        return $applications;
    }
}
