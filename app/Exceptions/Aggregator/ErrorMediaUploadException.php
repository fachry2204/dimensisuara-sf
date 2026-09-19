<?php

namespace App\Exceptions\Aggregator;

use Exception;

class ErrorMediaUploadException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
