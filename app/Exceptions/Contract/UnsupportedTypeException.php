<?php

namespace App\Exceptions\Contract;

use Exception;

class UnsupportedTypeException extends Exception
{
    public function __construct($type, array $allowed = [])
    {
        parent::__construct("Unsupported contract type: : " . $type . ". Allowed type: " . implode(', ', $allowed));
    }
}
