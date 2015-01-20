<?php

namespace jalder\Upnp\Mediaserver;

class Server
{

    private $request;
    public $parsed;

    public function __construct()
    {


    }

    public static function forge($request)
    {
        $server = new Server();
        $server->setRequest($request);
        $server->parse();
        return $server;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function parse()
    {
        //do dom work

        var_dump($this->request);
        $this->parsed = [
            'ObjectID'=>'10',
            'BrowseFlag'=>'BrowseDirectChildren',
            'Filter'=>'',
            'StartingIndex'=>0,
            'RequestedCount'=>0
        ];
    }
}
