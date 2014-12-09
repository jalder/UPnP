PHP UPnP Library
================
PHP Library for Interacting with UPnP Network Devices
-----------------------------------------------------

###Work In Progress.

### Description
This library aims to be a convenient set of classes for controlling UPnP devices on a network. Some service specific classes for devices that leverage UPnP/SSDP may also be included such as the Roku (ecp and simplevideoplayer), Chromecast (castv2), XBMC.  Any device that can be discovered via SSDP will be considered for inclusion.  Should mDNS be implemented in the future, package name may be changed to better reflect its role/purpose.

### Requirements
PHP Protocol Buffer module needs to be compiled and installed in your environment for communicating protobuf binary messages with chromecasts.  This is only needed for controlling Chromecasts, module can be ignored for pure UPnP implementations or when using other supported devices.  See Credits and Acknowledgements for further details.

### Installation
Package is composer compliant using PSR-4 autoloader.

```
{
    "require": {
        "jalder/upnp": "dev-master"
    }
}
```

### Examples

##### UPnP Mediaserver Browsing

```
$ms = new Mediaserver();
$servers = $ms->discover();

foreach($servers as $s){
    $browser = new Mediaserver\Browser($s);
    $directories = $browser->browse();  //pass the parent id as $browser->browse($id); to traverse the tree.
    print_r($directories);
}

```
##### UPnP Renderer Control

```
$rs = new Renderer();
$renderers = $rs->discover();

foreach($renderers as $r){
    $remote = new Renderer\Remote($r);
    $remote->play();  //or $remote->play($url); to start playing a video
}

```
##### Discover all UPnP devices

```
$upnp = new Upnp();
$devices = $upnp->discover();
print_r($devices);

```
##### Roku Devices

```
$rokus = new Roku();

foreach($rokus->discover() as $roku){
    $remote = new Roku\Remote($roku); //optionally, pass an application to control $remote = new Roku\Remote($roku,'firefox');
    $remote->play(); //all ECP buttons are supported, play (toggle), okay, back, home, dPad, forward, rewind, launch app
    $remote->play($url);  //if the simplevideoplayer example app is sideloaded on your roku, you can automatically launch playback of videos as well. Use case often involves starting playback of a video item discovered with the Mediaserver methods.
}

```
##### Chromecast Devices

```
$chromecasts = new Chromecast();
$application = new Chromecast\Applications\DefaultMediaReceiver();  //select the application we are speaking with

foreach($chromecasts->discover() as $chromecast){
    $remote = new Chromecast\Remote($chromecast, $application);  //get the remote and pass to the remote the application it will manage
    $remote->play($url);  //will start playback of video using the default media receiver chromecast application.
}

```

###Credits & Acknowledgements
phpupnp.class.php from artheus/PHP-UPnP https://github.com/artheus/PHP-UPnP , working example of using sockets in PHP for SSDP.

Chromecast protocol description https://github.com/thibauts/node-castv2#protocol-description written by thibauts.

PHP implementation of Google's Protocol Buffer by allegro for encoding chromecast communication payloads in allegro/php-protobuf https://github.com/allegro/php-protobuf.

###Work In Progress.

http://jalder.com

###LICENSE 
MIT
