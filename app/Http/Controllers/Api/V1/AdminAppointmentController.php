<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AdminAssignAppointmentRequest;
use App\Http\Requests\Appointment\AdminStoreAppointmentRequest;
use App\Http\Requests\Appointment\AdminUpdateAppointmentRequest;
use App\Http\Requests\Appointment\AdminUpdateAppointmentStatusRequest;
use App\Http\Requests\Appointment\CancelAppointmentRequest;
use App\Http\Requests\Appointment\RescheduleAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\User;
use App\Services\Appointments\AppointmentAvailabilityService;
use App\Services\Appointments\AppointmentLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminAppointmentController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function __construct(
        protected AppointmentAvailabilityService $availabilityService,
        protected AppointmentLifecycleService $lifecycleService,
    ) {
    }

    public function index(Request $request)
    {
        $query = Appointment::query()->with(['service', 'assignedEmployee', 'user']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($serviceId = $request->query('service_id')) {
            $query->whereHas('service', fn ($q) => $q->where('public_id', $serviceId));
        }
        if ($employeeId = $request->query('employee_id')) {
            $query->whereHas('assignedEmployee', fn ($q) => $q->where('public_id', $employeeId));
        }
        if ($date = $request->query('date')) {
            $query->whereBetween('starts_at', [
                Carbon::parse($date, $this->availabilityService->settings()->business_timezone)->startOfDay()->setTimezone('UTC'),
                Carbon::parse($date, $this->availabilityService->settings()->business_timezone)->endOfDay()->setTimezone('UTC'),
            ]);
        }

        $page = $query->latest('starts_at')->paginate($this->resolvePerPage($request));
        return $this->success(AppointmentResource::collection($page)->response()->getData(true));
    }

    public function slots(Request $request)
    {
        $request->validate([
            'service_id' => ['required', 'string', 'exists:appointment_services,public_id'],
            'date' => ['required', 'date'],
            'employee_id' => ['nullable', 'string', 'exists:users,public_id'],
        ]);

        $service = AppointmentService::query()->where('public_id', $request->query('service_id'))->firstOrFail();
        $employee = $request->filled('employee_id') ? User::query()->where('public_id', $request->query('employee_id'))->firstOrFail() : null;
        $slots = $this->availabilityService->availableSlots($service, Carbon::parse($request->query('date'), $this->availabilityService->settings()->business_timezone), $employee, true);

        return $this->success(['data' => $slots]);
    }

    public function store(AdminStoreAppointmentRequest $request)
    {
        $appointment = $this->lifecycleService->createInternal($request->user('api'), $request->validated());
        return $this->success(new AppointmentResource($appointment), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if (! $this->canAccessAppointment($request->user('api'), $appointment)) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }

        return $this->success(new AppointmentResource($appointment->load(['service', 'assignedEmployee', 'user'])));
    }

    public function update(AdminUpdateAppointmentRequest $request, Appointment $appointment)
    {
        if (! $request->user('api')->can('appointments.bookings.manage')) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }

        $appointment->fill($request->validated());
        $appointment->save();
        \App\Services\AuditLogger::log('appointment.updated', $appointment, ['fields' => array_keys($request->validated())]);

        return $this->success(new AppointmentResource($appointment->fresh(['service', 'assignedEmployee', 'user'])));
    }

    public function assign(AdminAssignAppointmentRequest $request, Appointment $appointment)
    {
        $appointment = $this->lifecycleService->assignEmployee($appointment, $request->user('api'), $request->validated('assigned_employee_id'));
        return $this->success(new AppointmentResource($appointment));
    }

    public function updateStatus(AdminUpdateAppointmentStatusRequest $request, Appointment $appointment)
    {
        if (! $this->canAccessAppointment($request->user('api'), $appointment)) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }

        $appointment = $this->lifecycleService->updateStatus($appointment, $request->user('api'), $request->validated('status'));
        return $this->success(new AppointmentResource($appointment));
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment)
    {
        if (! $request->user('api')->can('appointments.bookings.manage')) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }

        $appointment = $this->lifecycleService->cancel($appointment, $request->user('api'), $request->validated('reason'), false);
        return $this->success(new AppointmentResource($appointment));
    }

    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment)
    {
        if (! $request->user('api')->can('appointments.bookings.manage')) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }

        $appointment = $this->lifecycleService->reschedule($appointment, $request->user('api'), $request->validated('starts_at'), false);
        return $this->success(new AppointmentResource($appointment));
    }

    private function canAccessAppointment(User $user, Appointment $appointment): bool
    {
        if ($user->can('appointments.bookings.view_all') || $user->can('appointments.bookings.manage')) {
            return true;
        }

        return $appointment->assigned_employee_user_id === $user->id || $appointment->created_by_user_id === $user->id;
    }
}
