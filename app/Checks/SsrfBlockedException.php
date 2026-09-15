<?php

namespace App\Checks;

class SsrfBlockedException extends \RuntimeException
{
    public function __construct(public readonly string $errorClass, string $message)
    {
        parent::__construct($message);
    }
}
