<?php

namespace App\Domain\Policy\Exceptions;

use RuntimeException;

class PolicyReadOnlyException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Policy is read-only: {$key}");
    }
}
