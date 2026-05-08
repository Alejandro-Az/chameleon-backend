<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\CancelAppointmentRequest;
use App\Http\Requests\Appointment\RescheduleAppointmentRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Services\Appointments\AppointmentAvailabilityService;
use App\Services\Appointments\AppointmentLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
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
        $query = Appointment::query()
            ->with(['service', 'assignedEmployee', 'user'])
            ->where('user_id', $request->user('api')->id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $page = $query->latest('starts_at')->paginate($this->resolvePerPage($request));
        return $this->success(AppointmentResource::collection($page)->response()->getData(true));
    }

    public function slots(Request $request)
    {
        $request->validate([
            'service_id' => ['required', 'string', 'exists:appointment_services,public_id'],
            'date' => ['required', 'date'],
        ]);

        $service = AppointmentService::query()->where('public_id', $request->query('service_id'))->firstOrFail();
        $slots = $this->availabilityService->availableSlots($service, Carbon::parse($request->query('date'), $this->availabilityService->settings()->business_timezone), null, false);

        return $this->success(['data' => $slots]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $appointment = $this->lifecycleService->createForUser($request->user('api'), $request->validated());
        return $this->success(new AppointmentResource($appointment), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user('api')->id) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }
        return $this->success(new AppointmentResource($appointment->load(['service', 'assignedEmployee', 'user'])));
    }

    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user('api')->id) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }
        $appointment = $this->lifecycleService->reschedule($appointment, $request->user('api'), $request->validated('starts_at'), true);
        return $this->success(new AppointmentResource($appointment));
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user('api')->id) {
            return $this->error('AUTH_FORBIDDEN', 'No tienes permiso para realizar esta acci�n.', 403);
        }
        $appointment = $this->lifecycleService->cancel($appointment, $request->user('api'), $request->validated('reason'), true);
        return $this->success(new AppointmentResource($appointment));
    }
}
