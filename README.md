PHP UPnP Library
================
PHP Library for Interacting with UPnP Network Devices
-----------------------------------------------------

### Composer PSR-4 compliant UPnP library.
Work In Progress

### Examples

```
$ms = new Mediaserver();
$servers = $ms->discover();

foreach($servers as $s){
    $browser = new Mediaserver\Browser($s);
    //get root level directories
    $directories = $browser->browse();
}

$rs = new Renderer();
$renderers = $rs->discover();

foreach($renderers as $r){
    $remote = new Renderer\Remote($r);
    //control the renderer
    $remote->play();
}

//discover all UPnP Devices
$upnp = new Core();
$devices = $upnp->discover();

print_r($devices);
```

Work In Progress.

http://jalder.com

LICENSE: MIT
