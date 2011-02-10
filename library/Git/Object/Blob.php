<?php

namespace Git\Object;

use Git;

class Blob
    extends Git\Object
{
    protected $_content = null;

    public function __construct($hash, $data)
    {
        parent::__construct($hash, $rawContent);

        $this->_content = $data;
    }

    public function __toString()
    {
        return $this->_content;
    }

    public function serialize()
    {
        return self::BLOB . ' ' . strlen($this->_content) . "\0" . $this->__toString();
    }

    public function unserialize($serialized)
    {
        $this->_content = substr($serialized, strpos($serialized, "\0"));
    }
}