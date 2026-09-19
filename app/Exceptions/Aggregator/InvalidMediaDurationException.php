<?php

namespace App\Exceptions\Aggregator;

use Exception;

class InvalidMediaDurationException extends Exception
{
    protected $currentDuration;
    protected $allowedDuration;

    public function __construct($currentDuration, int $allowedDuration)
    {
        $this->currentDuration = round($currentDuration, 0);
        $this->allowedDuration = $allowedDuration;

        parent::__construct('Invalid media duration: ' . $this->currentDuration . ' seconds. Allowed duration equal or less than ' . $this->allowedDuration . ' seconds.');
    }
}
