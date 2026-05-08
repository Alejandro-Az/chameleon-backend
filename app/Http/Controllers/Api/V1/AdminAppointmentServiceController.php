<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AdminStoreAppointmentServiceRequest;
use App\Http\Requests\Appointment\AdminUpdateAppointmentServiceRequest;
use App\Http\Resources\AppointmentServiceResource;
use App\Models\AppointmentService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAppointmentServiceController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(Request $request)
    {
        $query = AppointmentService::query()->with('staff');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where('name', 'like', "%{$q}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        $page = $query->latest()->paginate($this->resolvePerPage($request));
        return $this->success(AppointmentServiceResource::collection($page)->response()->getData(true));
    }

    public function store(AdminStoreAppointmentServiceRequest $request)
    {
        $service = DB::transaction(function () use ($request) {
            $service = AppointmentService::query()->create($request->safe()->except('staff_ids'));
            $this->syncStaff($service, $request->validated('staff_ids', []));
            \App\Services\AuditLogger::log('appointment.service.created', $service, ['staff_ids' => $request->validated('staff_ids', [])]);
            return $service->fresh(['staff']);
        });

        return $this->success(new AppointmentServiceResource($service), 201);
    }

    public function show(AppointmentService $appointmentService)
    {
        return $this->success(new AppointmentServiceResource($appointmentService->load('staff')));
    }

    public function update(AdminUpdateAppointmentServiceRequest $request, AppointmentService $appointmentService)
    {
        DB::transaction(function () use ($request, $appointmentService) {
            $appointmentService->fill($request->safe()->except('staff_ids'));
            $appointmentService->save();
            if ($request->has('staff_ids')) {
                $this->syncStaff($appointmentService, $request->validated('staff_ids', []));
            }
            \App\Services\AuditLogger::log('appointment.service.updated', $appointmentService, ['fields' => array_keys($request->validated())]);
        });

        return $this->success(new AppointmentServiceResource($appointmentService->fresh(['staff'])));
    }

    public function destroy(AppointmentService $appointmentService)
    {
        $appointmentService->delete();
        \App\Services\AuditLogger::log('appointment.service.deleted', $appointmentService);

        return $this->success(['message' => 'Servicio eliminado correctamente.']);
    }

    private function syncStaff(AppointmentService $service, array $staffIds): void
    {
        $ids = User::query()->whereIn('public_id', $staffIds)->pluck('id')->all();
        $service->staff()->sync($ids);
    }
}
