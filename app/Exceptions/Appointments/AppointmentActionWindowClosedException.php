<?php

namespace App\Exceptions\Appointments;

use RuntimeException;

class AppointmentActionWindowClosedException extends RuntimeException
{
    public function __construct(
        protected string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
