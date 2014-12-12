PHP UPnP Library
================
PHP Library for Interacting with UPnP (SSDP) Network Devices
-----------------------------------------------------

###Work In Progress.

### Description
This library aims to be a convenient set of classes for controlling UPnP devices on a network. Some service specific classes for devices that leverage UPnP/SSDP may also be included such as the Roku (ecp, simplevideoplayer, and firefox), Chromecast (castv2), XBMC.  Any device that can be discovered via SSDP will be considered for inclusion.  Should mDNS be implemented in the future, package name may be changed to better reflect its role/purpose.

### Requirements
PHP 5.4 or greater

PHP Protocol Buffer module needs to be compiled and installed in your environment for communicating protobuf binary messages with chromecasts.  This is only needed for controlling Chromecasts, module can be ignored for pure UPnP implementations or when using other supported devices.  See Credits and Acknowledgements for further details.

### Installation
Package is available via packagist and is PSR-4 compliant.

```
{
    "require": {
        "jalder/upnp": "dev-master"
    }
}
```

### Examples

Multiple [examples](examples/) have been written to demonstrate basic usage for various device types.

However, below is a simple Chromecast example.  The example will launch a movie, run for 30 seconds, maintaining socket connection, and die;

##### Chromecast Devices

```
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

```

###Credits & Acknowledgements
phpupnp.class.php from artheus/PHP-UPnP https://github.com/artheus/PHP-UPnP , working example of using sockets in PHP for SSDP.

Chromecast protocol description https://github.com/thibauts/node-castv2#protocol-description written by thibauts.

PHP implementation of Google's Protocol Buffer by allegro for encoding chromecast communication payloads in allegro/php-protobuf https://github.com/allegro/php-protobuf.

###Work In Progress.

http://jalder.com

###LICENSE 
The MIT License (MIT)

Copyright (c) 2014 John Alder

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
