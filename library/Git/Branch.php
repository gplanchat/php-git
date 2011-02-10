<?php

namespace Git;

class Branch
{
    protected $_name = null;
    protected $_commit = null;

    public function __construct($name, $commit)
    {
        $this->_name = $name;
        $this->_commit = $commit;
    }
}