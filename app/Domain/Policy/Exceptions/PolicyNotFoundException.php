<?php

namespace App\Domain\Policy\Exceptions;

use RuntimeException;

class PolicyNotFoundException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Policy not found: {$key}");
    }
}
