<?php

namespace App\Domain\Policy\Exceptions;

use RuntimeException;

class PolicyConflictException extends RuntimeException
{
    public function __construct(string $message = 'Policy conflict')
    {
        parent::__construct($message);
    }
}
