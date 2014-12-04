<?php

namespace jalder\Upnp\Chromecast\Channels;


interface Channel
{
    public function addMessage($message, $execute);
    public function getMessages();
}
