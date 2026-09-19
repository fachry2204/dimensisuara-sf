<?php

namespace App\Exceptions\Aggregator;

use Exception;

class InvalidFileFormatException extends Exception
{
    protected $currentFileFormat;
    protected $allowedFileFormats;

    public function __construct($currentFileFormat, array $allowedFileFormats)
    {
        $this->currentFileFormat = $currentFileFormat;
        $this->allowedFileFormats = $allowedFileFormats;
        
        parent::__construct('Invalid file format: ' . $currentFileFormat . '. Allowed formats: ' . implode(', ', $allowedFileFormats));
    }
}
