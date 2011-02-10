<?php

namespace Git\Object;

use Git;

class Commit
    extends Git\Object
{
    protected $_tree = null;

    protected $_parents = array();

    protected $_authors = array();

    protected $_committer = null;

    protected $_summary = null;

    protected $_message = null;

    public function __construct($hash, $rawContent)
    {
        parent::__construct($hash, $rawContent);

        $offsetStart = 0;
        $offsetEnd = 0;
        $length = strlen($rawContent);
        while ($offsetStart < $length) {
            if (($offsetEnd = strpos($rawContent, "\n", $offsetStart)) === false) {
                $offsetEnd = $length;
            }

            $line = substr($rawContent, $offsetStart, $offsetEnd - $offsetStart);
            if ($line === '') {
                $this->_message = substr($rawContent, $offsetStart + 1);
                break;
            }

            $offset = strpos($line, ' ');
            switch (substr($line, 0, $offset)) {
            case 'tree':
                $this->_tree = substr($line, $offset + 1);
                break;

            case 'parent':
                $this->_parents[] = substr($line, $offset + 1);
                break;

            case 'committer':
                $this->_committer = substr($line, $offset + 1);
                break;

            case 'author':
                $this->_authors[] = substr($line, $offset + 1);
            }

            $offsetStart = $offsetEnd + 1;
        }
        $offset = strpos($this->_message, "\n");
        if ($offset === false) {
            $this->_summary = $this->_message;
        } else {
            $this->_summary = substr($this->_message, 0, $offset);
        }
    }

    public function serialize()
    {
        return '';
    }

    public function unserialize($serialized)
    {
    }
}