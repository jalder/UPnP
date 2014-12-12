<?php

require(dirname(__FILE__).'/../../../autoload.php');

use jalder\Upnp\Renderer;

$renderer = new Renderer();

print('searching...'.PHP_EOL);

$renderers = $renderer->discover();
$movie = 'https://ia700408.us.archive.org/26/items/BigBuckBunny_328/BigBuckBunny_512kb.mp4';

if(!count($renderers)){
    print_r('no upnp renderers found'.PHP_EOL);
}

foreach($renderers as $r){
    print($r['description']['device']['friendlyName']);
    $remote = new Renderer\Remote($r);
    $result = $remote->play($movie);
    print_r($result);
}

