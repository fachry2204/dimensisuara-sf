<?php

namespace App\Exceptions\Contract;

use Exception;

class NumberAlreadyUsedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Contract number and year combination already exists.');
    }
}
