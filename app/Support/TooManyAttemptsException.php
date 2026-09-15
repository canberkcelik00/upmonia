<?php

namespace App\Support;

use Exception;

class TooManyAttemptsException extends Exception
{
    public function __construct(public readonly int $availableInSeconds)
    {
        parent::__construct("Too many attempts. Available in {$availableInSeconds}s.");
    }
}
