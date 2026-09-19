<?php

namespace App\Exceptions\Aggregator;

use Exception;

class ErrorCheckingMediaDurationException extends Exception
{
    public function __construct()
    {
        parent::__construct('Error checking media duration');
    }
}
