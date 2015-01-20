<?php

require(dirname(__FILE__).'/../../../autoload.php');

use jalder\Upnp\Dial;
use jalder\Upnp\Core;

$dial = new Dial();
$core = new Core();

print('searching...'.PHP_EOL);

$dials = $dial->discover();
$registry = $dial->getRegistry();
//var_dump($registry);

foreach($dials as $d){
    print($d['description']['device']['friendlyName'].PHP_EOL);
    $location = $d['location'];
    $appurl = $dial->getApplicationUrl($location);
//    var_dump($appurl);
    //    die();
    $i = 1;
    foreach($registry as $app){
//        print($appurl.$app['app'].PHP_EOL);
        //continue;
//        print('processing '.$i.' of '.count($registry).PHP_EOL);
        $i++;
        if($i > 10){
            continue;
        }
        $alive = $dial->getApp($appurl.urlencode($app['app']));
        if($alive){
            print($app['app'].' exists'.PHP_EOL);
            if($app['app'] === 'YouTube'){
                $remote = new jalder\Upnp\Dial\Remote($appurl);
                $remote->loadApp(urlencode($app['app']));
            }
            var_dump($dial->getDescription($appurl.urlencode($app['app'])));
        }
    }
}

