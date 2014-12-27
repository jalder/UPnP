<?php

namespace jalder\Upnp\Chromecast\Channels;

class CastCommander extends Socket
{
    private $stream;

    public function __construct($stream)
    {
            $this->message = new \CastMessage();  //protobuf for outgoing
            $this->replies = new \CastMessage();  //protobuf for incoming
            $this->message->setProtocolVersion('CASTV2_1_0');  //0
            $this->message->setSourceId($this->sourceId);
            $this->message->setDestinationId($this->destinationId);
            $this->message->setPayloadType('STRING');  //0
            $this->stream = $stream;
            $this->verbosity = 10;
    }

    public function execute()
    {
        $this->writeQueue('all');
    }

    public function writeMessage($message)
    {
        $payload = $this->buildPayload($message);
        $this->stream->write($payload);
    }
}
