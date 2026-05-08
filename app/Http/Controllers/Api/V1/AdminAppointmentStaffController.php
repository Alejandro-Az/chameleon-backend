<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AdminStoreAppointmentStaffRequest;
use App\Http\Requests\Appointment\AdminUpdateAppointmentStaffRequest;
use App\Http\Resources\AppointmentStaffProfileResource;
use App\Models\AppointmentStaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAppointmentStaffController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(Request $request)
    {
        $query = AppointmentStaffProfile::query()->with(['user', 'weekdayRules', 'exceptions']);
        if ($q = trim((string) $request->query('q', ''))) {
            $query->whereHas('user', function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $page = $query->latest()->paginate($this->resolvePerPage($request));
        return $this->success(AppointmentStaffProfileResource::collection($page)->response()->getData(true));
    }

    public function store(AdminStoreAppointmentStaffRequest $request)
    {
        $user = User::query()->where('public_id', $request->validated('user_id'))->firstOrFail();

        $profile = DB::transaction(function () use ($request, $user) {
            $profile = AppointmentStaffProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $request->safe()->except('user_id'),
            );
            if (! $user->hasRole('employee')) {
                $user->assignRole('employee');
            }
            \App\Services\AuditLogger::log('appointment.staff.created', $profile, ['user_id' => $user->public_id]);
            return $profile->fresh(['user', 'weekdayRules', 'exceptions']);
        });

        return $this->success(new AppointmentStaffProfileResource($profile), 201);
    }

    public function show(AppointmentStaffProfile $appointmentStaffProfile)
    {
        return $this->success(new AppointmentStaffProfileResource($appointmentStaffProfile->load(['user', 'weekdayRules', 'exceptions'])));
    }

    public function update(AdminUpdateAppointmentStaffRequest $request, AppointmentStaffProfile $appointmentStaffProfile)
    {
        $appointmentStaffProfile->fill($request->validated());
        $appointmentStaffProfile->save();
        \App\Services\AuditLogger::log('appointment.staff.updated', $appointmentStaffProfile, ['fields' => array_keys($request->validated())]);

        return $this->success(new AppointmentStaffProfileResource($appointmentStaffProfile->fresh(['user', 'weekdayRules', 'exceptions'])));
    }

    public function destroy(AppointmentStaffProfile $appointmentStaffProfile)
    {
        $user = $appointmentStaffProfile->user;
        $user?->appointmentServices()->detach();
        $appointmentStaffProfile->delete();
        \App\Services\AuditLogger::log('appointment.staff.deleted', $appointmentStaffProfile);

        return $this->success(['message' => 'Perfil de staff eliminado correctamente.']);
    }
}
