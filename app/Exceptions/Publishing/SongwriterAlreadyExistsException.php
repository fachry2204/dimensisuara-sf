<?php

namespace App\Exceptions\Publishing;

use Exception;

class SongwriterAlreadyExistsException extends Exception
{
    public function __construct($email)
    {
        parent::__construct("Songwriter with email $email already exists under your profile.");
    }
}
