<?php

namespace App\Exceptions\Settlement;

use Exception;

class InvalidStatusException extends Exception
{
    public function __construct($status, array $allowed = [])
    {
        parent::__construct("Invalid settlement status: " . $status . ". Allowed statuses: " . implode(', ', $allowed));
    }
}
