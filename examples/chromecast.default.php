<?php

require(dirname(__FILE__).'/../../../autoload.php');

use jalder\Upnp\Chromecast;

$chromecast = new Chromecast();

print('searching...'.PHP_EOL);

$chromecasts = $chromecast->discover();
$movie = 'https://ia700408.us.archive.org/26/items/BigBuckBunny_328/BigBuckBunny_512kb.mp4';

if(!count($chromecasts)){
    print_r('no chromecasts found via SSDP'.PHP_EOL);
}

foreach($chromecasts as $c){
    print($c['description']['device']['friendlyName'].PHP_EOL);
    $app = new Chromecast\Applications\DefaultMediaReceiver($c);
    $remote = new Chromecast\Remote($c, $app);
    $result = $remote->play($movie);
    print_r($result);
}

