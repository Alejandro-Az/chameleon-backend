<?php

namespace App\Exceptions\Appointments;

use RuntimeException;

class AppointmentSlotUnavailableException extends RuntimeException
{
    public function __construct(
        protected array $details,
        string $message = 'El horario solicitado no est� disponible.',
    ) {
        parent::__construct($message);
    }

    public function details(): array
    {
        return $this->details;
    }
}
