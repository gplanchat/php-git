<?php

namespace Git;

abstract class Object
    implements \Serializable
{
    const OBJ_COMMIT    = 1;
    const OBJ_TREE      = 2;
    const OBJ_BLOB      = 3;
    const OBJ_TAG       = 4;
    const OBJ_OFS_DELTA = 6;
    const OBJ_REF_DELTA = 7;

    const COMMIT    = 'commit';
    const TREE      = 'tree';
    const BLOB      = 'blob';
    const TAG       = 'tag';

    protected $_hash = null;

    public function __construct($hash, $rawContent)
    {
        $this->_hash = $hash;
    }
}