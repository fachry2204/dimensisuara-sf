<?php

namespace App\Exceptions\Publishing;

use Exception;

class InvalidApprovalStatusException extends Exception
{
    public function __construct()
    {
        parent::__construct('This request is not available for approval.');
    }
}
