<?php

namespace App\Exceptions\Publishing;

use Exception;

class InvalidSendingStatusException extends Exception
{
    public function __construct($message = "Cannot send notification for request with status other than 'pending'.")
    {
        parent::__construct($message);
    }
}
