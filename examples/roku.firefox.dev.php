<?php

require(dirname(__FILE__).'/../../../autoload.php');

use jalder\Upnp\Roku;

$roku = new Roku();

print('searching...'.PHP_EOL);

$rokus = $roku->discover();
$movie = ['url'=>'http://192.168.1.20:32469/object/caa889ec7293408d83c9/file.ts','format'=>'ts','subtitle_url'=>'','title'=>'Test .ts File'];

if(!count($rokus)){
    print_r('no rokus found via SSDP'.PHP_EOL);
}

foreach($rokus as $r){
    print($r['description']['device']['friendlyName'].PHP_EOL);
    $app = new Roku\Applications\Firefox($r);
    $app->setAppId('dev');
    $remote = new Roku\Player($r, $app);
    $result = $remote->play($movie);
    print_r($result);
}

