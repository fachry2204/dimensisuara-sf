<?php

namespace App\Exceptions\Settlement;

use Exception;

class SessionDataNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Generated settlements data not found. Please regenerate the settlements.');
    }
}
