<?php

namespace App\Exceptions\Aggregator;

use Exception;

class InvalidAudioBitDepthException extends Exception
{
    protected $currentBitDepth;
    protected $allowedBitDepths;

    public function __construct($currentBitDepth, array $allowedBitDepths)
    {
        $this->currentBitDepth = $currentBitDepth;
        $this->allowedBitDepths = $allowedBitDepths;

        parent::__construct('Invalid audio bit depth: ' . ($currentBitDepth ?? 'unknown') . ' bits. Supported bit depth is ' . implode(' or ', $allowedBitDepths) . ' bit.');
    }
}
