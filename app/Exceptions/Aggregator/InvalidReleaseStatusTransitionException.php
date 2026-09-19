<?php

namespace App\Exceptions\Aggregator;

use Exception;

class InvalidReleaseStatusTransitionException extends Exception
{
    public function __construct(string $statusBefore, string $statusAfter)
    {
        parent::__construct("Cannot transition release from " . $statusBefore . " to " . $statusAfter . ".");
    }
}
