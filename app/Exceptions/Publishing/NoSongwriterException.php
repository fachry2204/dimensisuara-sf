<?php

namespace App\Exceptions\Publishing;

use Exception;

class NoSongwriterException extends Exception
{
    public function __construct($message = "No songwriter found. Please add a songwriter first from the songwriter tab.")
    {
        parent::__construct($message);
    }
}
