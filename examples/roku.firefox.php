<?php

require(dirname(__FILE__).'/../../../autoload.php');

use jalder\Upnp\Roku;

$roku = new Roku();

print('searching...'.PHP_EOL);

$rokus = $roku->discover();
$movie = ['url'=>'https://ia700408.us.archive.org/26/items/BigBuckBunny_328/BigBuckBunny_512kb.mp4','format'=>'mp4','subtitle_url'=>'','title'=>'Big Buck Bunny'];

if(!count($rokus)){
    print_r('no rokus found via SSDP'.PHP_EOL);
}

foreach($rokus as $r){
    print($r['description']['device']['friendlyName'].PHP_EOL);
    $app = new Roku\Applications\Firefox($r);
    $remote = new Roku\Player($r, $app);
    $result = $remote->play($movie);
    print_r($result);
}

