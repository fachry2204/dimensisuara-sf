<?php

namespace App\Exceptions\Publishing;

use Exception;

class InvalidSongwriterException extends Exception
{
    public function __construct()
    {
        parent::__construct('Songwriter data is not completed. Please complete the songwriter data first.');
    }
}
