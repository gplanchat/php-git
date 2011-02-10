<?php

namespace Git\Adapter\Exception;

use Git;

class RuntimeException
    extends \RuntimeException
    implements Git\Exception
{
}