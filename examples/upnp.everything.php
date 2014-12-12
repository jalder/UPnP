<?php

require(dirname(__FILE__).'/../../../autoload.php');

use jalder\Upnp\Upnp;

$upnp = new Upnp();

print('searching...'.PHP_EOL);

$everything = $upnp->discover();

if(!count($everything)){
    print_r('no upnp devices found'.PHP_EOL);
}

foreach($everything as $device){
    //print_r($device);  //uncomment to see all available array elements for a device.
    $info = $device['description']['device'];
    $summary = $info['friendlyName'].', '.$info['modelName'].', '.$info['UDN'];
    print($summary.PHP_EOL);
}

