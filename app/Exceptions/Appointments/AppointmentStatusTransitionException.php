<?php

namespace App\Exceptions\Appointments;

use RuntimeException;

class AppointmentStatusTransitionException extends RuntimeException
{
    public function __construct(
        protected string $fromStatus,
        protected string $toStatus,
        string $message = 'La transici�n de estado solicitada no es v�lida.',
    ) {
        parent::__construct($message);
    }

    public function fromStatus(): string
    {
        return $this->fromStatus;
    }

    public function toStatus(): string
    {
        return $this->toStatus;
    }
}
