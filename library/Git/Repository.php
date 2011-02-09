<?php

namespace Git;

use Git\Adapter;

class Repository
{
    public static function factory($uri, $options = array())
    {
        $options['path'] = $uri;
        return new Adapter\FileSystem($options);
    }
}
