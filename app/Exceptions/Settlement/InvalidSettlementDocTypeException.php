<?php

namespace App\Exceptions\Settlement;

use Exception;

class InvalidSettlementDocTypeException extends Exception
{
    public function __construct($type, array $allowed = [])
    {
        parent::__construct("Invalid settlement document type: " . $type . ". Allowed typees: " . implode(', ', $allowed));
    }
}
