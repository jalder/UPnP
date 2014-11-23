PHP UPnP Library
================
PHP Library for Interacting with UPnP Network Devices
-----------------------------------------------------

Composer PSR-4 compliant UPnP library.

Work In Progress.

### Description
This library aims to be a convenient set of classes for controlling UPnP devices on a network. Some service specific classes for devices that leverage UPnP/SSDP may also be included such as the Roku (ecp and simplevideoplayer), Chromecast (castv2), XBMC (xbmc json api).

### Installation


### Examples

```
$ms = new Mediaserver();
$servers = $ms->discover();

foreach($servers as $s){
    $browser = new Mediaserver\Browser($s);
    $directories = $browser->browse();
}

$rs = new Renderer();
$renderers = $rs->discover();

foreach($renderers as $r){
    $remote = new Renderer\Remote($r);
    $remote->play();
}

//discover all UPnP Devices
$upnp = new Upnp();
$devices = $upnp->discover();

```

Work In Progress.

http://jalder.com

LICENSE: MIT
