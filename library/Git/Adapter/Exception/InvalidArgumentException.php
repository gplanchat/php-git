<?php

namespace Git\Adapter\Exception;

use Git;

class InvalidArgumentException
    extends \InvalidArgumentException
    implements Git\Exception
{
}