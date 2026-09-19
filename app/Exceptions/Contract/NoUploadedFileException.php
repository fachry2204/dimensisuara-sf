<?php

namespace App\Exceptions\Contract;

use Exception;

class NoUploadedFileException extends Exception
{
    public function __construct()
    {
        parent::__construct('No PDF has been uploaded');
    }
}
