<?php

namespace App\Exceptions\Aggregator;

use Exception;

class InvalidCoverResolutionException extends Exception
{
    protected $width;
    protected $height;
    protected $allowedWidth;
    protected $allowedHeight;

    public function __construct($width, $height, $allowedWidth, $allowedHeight)
    {
        $this->width = $width;
        $this->height = $height;
        $this->allowedWidth = $allowedWidth;
        $this->allowedHeight = $allowedHeight;

        parent::__construct('Invalid cover resolution: ' . $width . 'x' . $height . ' px. Allowed resolution: ' . $allowedWidth . 'x' . $allowedHeight . 'px.');
    }
}
