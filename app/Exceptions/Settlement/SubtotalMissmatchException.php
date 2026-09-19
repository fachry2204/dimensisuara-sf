<?php

namespace App\Exceptions\Settlement;

use Exception;

class SubtotalMissmatchException extends Exception
{
    public function __construct($sum, $expected)
    {
        parent::__construct('Subtotal missmatch found between ' . $sum . ' and ' . $expected );
    }
}
