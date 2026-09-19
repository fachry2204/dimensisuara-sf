<?php

namespace App\Exceptions\Publishing;

use Exception;

class InvalidRequestStatusException extends Exception
{
    public function __construct($status, array $allowed = [])
    {
        parent::__construct("Cannot process publishing with the current status: " . $status . ". Allowed statuses: " . implode(', ', $allowed));
    }
}
