<?php
/**
 * Auto generated from message.proto at 2014-11-23 02:35:36
 */

/**
 * ProtocolVersion enum embedded in CastMessage message
 */
final class CastMessage_ProtocolVersion
{
    const CASTV2_1_0 = 0;

    /**
     * Returns defined enum values
     *
     * @return int[]
     */
    public function getEnumValues()
    {
        return array(
            'CASTV2_1_0' => self::CASTV2_1_0,
        );
    }
}

/**
 * PayloadType enum embedded in CastMessage message
 */
final class CastMessage_PayloadType
{
    const STRING = 0;
    const BINARY = 1;

    /**
     * Returns defined enum values
     *
     * @return int[]
     */
    public function getEnumValues()
    {
        return array(
            'STRING' => self::STRING,
            'BINARY' => self::BINARY,
        );
    }
}

/**
 * CastMessage message
 */
class CastMessage extends \ProtobufMessage
{
    /* Field index constants */
    const PROTOCOL_VERSION = 1;
    const SOURCE_ID = 2;
    const DESTINATION_ID = 3;
    const NNAMESPACE = 4;
    const PAYLOAD_TYPE = 5;
    const PAYLOAD_UTF8 = 6;
    const PAYLOAD_BINARY = 7;

    /* @var array Field descriptors */
    protected static $fields = array(
        self::PROTOCOL_VERSION => array(
            'name' => 'protocol_version',
            'required' => true,
            'type' => 5,
        ),
        self::SOURCE_ID => array(
            'name' => 'source_id',
            'required' => true,
            'type' => 7,
        ),
        self::DESTINATION_ID => array(
            'name' => 'destination_id',
            'required' => true,
            'type' => 7,
        ),
        self::NNAMESPACE => array(
            'name' => 'namespace',
            'required' => true,
            'type' => 7,
        ),
        self::PAYLOAD_TYPE => array(
            'name' => 'payload_type',
            'required' => true,
            'type' => 5,
        ),
        self::PAYLOAD_UTF8 => array(
            'name' => 'payload_utf8',
            'required' => false,
            'type' => 7,
        ),
        self::PAYLOAD_BINARY => array(
            'name' => 'payload_binary',
            'required' => false,
            'type' => 7,
        ),
    );

    /**
     * Constructs new message container and clears its internal state
     *
     * @return null
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Clears message values and sets default ones
     *
     * @return null
     */
    public function reset()
    {
        $this->values[self::PROTOCOL_VERSION] = null;
        $this->values[self::SOURCE_ID] = null;
        $this->values[self::DESTINATION_ID] = null;
        $this->values[self::NNAMESPACE] = null;
        $this->values[self::PAYLOAD_TYPE] = null;
        $this->values[self::PAYLOAD_UTF8] = null;
        $this->values[self::PAYLOAD_BINARY] = null;
    }

    /**
     * Returns field descriptors
     *
     * @return array
     */
    public function fields()
    {
        return self::$fields;
    }

    /**
     * Sets value of 'protocol_version' property
     *
     * @param int $value Property value
     *
     * @return null
     */
    public function setProtocolVersion($value)
    {
        return $this->set(self::PROTOCOL_VERSION, $value);
    }

    /**
     * Returns value of 'protocol_version' property
     *
     * @return int
     */
    public function getProtocolVersion()
    {
        return $this->get(self::PROTOCOL_VERSION);
    }

    /**
     * Sets value of 'source_id' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setSourceId($value)
    {
        return $this->set(self::SOURCE_ID, $value);
    }

    /**
     * Returns value of 'source_id' property
     *
     * @return string
     */
    public function getSourceId()
    {
        return $this->get(self::SOURCE_ID);
    }

    /**
     * Sets value of 'destination_id' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setDestinationId($value)
    {
        return $this->set(self::DESTINATION_ID, $value);
    }

    /**
     * Returns value of 'destination_id' property
     *
     * @return string
     */
    public function getDestinationId()
    {
        return $this->get(self::DESTINATION_ID);
    }

    /**
     * Sets value of 'namespace' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setNamespace($value)
    {
        return $this->set(self::NNAMESPACE, $value);
    }

    /**
     * Returns value of 'namespace' property
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->get(self::NNAMESPACE);
    }

    /**
     * Sets value of 'payload_type' property
     *
     * @param int $value Property value
     *
     * @return null
     */
    public function setPayloadType($value)
    {
        return $this->set(self::PAYLOAD_TYPE, $value);
    }

    /**
     * Returns value of 'payload_type' property
     *
     * @return int
     */
    public function getPayloadType()
    {
        return $this->get(self::PAYLOAD_TYPE);
    }

    /**
     * Sets value of 'payload_utf8' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setPayloadUtf8($value)
    {
        return $this->set(self::PAYLOAD_UTF8, $value);
    }

    /**
     * Returns value of 'payload_utf8' property
     *
     * @return string
     */
    public function getPayloadUtf8()
    {
        return $this->get(self::PAYLOAD_UTF8);
    }

    /**
     * Sets value of 'payload_binary' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setPayloadBinary($value)
    {
        return $this->set(self::PAYLOAD_BINARY, $value);
    }

    /**
     * Returns value of 'payload_binary' property
     *
     * @return string
     */
    public function getPayloadBinary()
    {
        return $this->get(self::PAYLOAD_BINARY);
    }
}
