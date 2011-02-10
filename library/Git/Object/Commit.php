<?php

namespace Git\Object;

use Git;

class Commit
    extends Git\Object
{
    protected $_tree = null;

    protected $_parents = array();

    protected $_author = null;

    protected $_commiter = null;

    protected $_summary = null;

    protected $_message = null;

    public function __construct($hash, $raw)
    {
        $this->_hash = $hash;
    }

    public function serialize()
    {
    }

    public function unserialize($serialized)
    {
    }
}