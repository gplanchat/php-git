<?php

namespace Git;

use Git\Adapter;

class Repository
{
    public static function factory($uri, $options)
    {
        return new Adapter\FileSystem($opitons);
    }

    public static function _resolveUri($uri)
    {

    }
}
