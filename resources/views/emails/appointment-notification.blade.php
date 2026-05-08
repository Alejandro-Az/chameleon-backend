<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827;">
    <h2>{{ $title }}</h2>
    <p>{{ $bodyMessage }}</p>
    <p><strong>Servicio:</strong> {{ $appointment->service?->name }}</p>
    <p><strong>Inicio:</strong> {{ $appointment->starts_at->copy()->setTimezone($appointment->business_timezone)->format('Y-m-d H:i') }} ({{ $appointment->business_timezone }})</p>
    <p><strong>Fin:</strong> {{ $appointment->ends_at->copy()->setTimezone($appointment->business_timezone)->format('Y-m-d H:i') }}</p>
    @if($appointment->assignedEmployee)
        <p><strong>Empleado asignado:</strong> {{ $appointment->assignedEmployee->name }}</p>
    @endif
</body>
</html>
