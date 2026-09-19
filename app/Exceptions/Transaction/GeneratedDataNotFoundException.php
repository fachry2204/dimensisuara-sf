<?php

namespace App\Exceptions\Transaction;

use Exception;

class GeneratedDataNotFoundException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
