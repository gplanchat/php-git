<?php

namespace Git;

class Tag
{
    protected $_name = null;
    protected $_commit = null;

    public function __construct($name, $commit)
    {
        $this->_name = $name;
        $this->_commit = $commit;
    }
}