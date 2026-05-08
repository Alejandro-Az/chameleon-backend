<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceAccountRequest;
use App\Http\Requests\Admin\UpdateServiceAccountRequest;
use App\Http\Resources\ServiceAccountResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminServiceAccountController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(Request $request)
    {
        $q = User::query()->where('type', 'service')->with('roles');

        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('public_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $perPage = $this->resolvePerPage($request);
        $page = $q->latest()->paginate($perPage);

        $payload = ServiceAccountResource::collection($page)->response()->getData(true);

        return $this->success($payload);
    }

    public function store(StoreServiceAccountRequest $request)
    {
        $data = $request->validated();
        /** @var User $actor */
        $actor = $request->user();

        $serviceAccount = DB::transaction(function () use ($data, $actor) {
            $email = $data['email'] ?? ('svc-' . (string) Str::ulid() . '@service.local');

            /** @var User $u */
            $u = User::create([
                'name'              => $data['name'],
                'email'             => $email,
                'username'          => null,
                'password'          => Str::random(64),
                'status'            => 'active',
                'meta'              => array_filter([
                    'description' => $data['description'] ?? null,
                ]),
            ]);

            // type and email_verified_at are NOT fillable → forceFill server-side
            $u->forceFill([
                'type'              => 'service',
                'email_verified_at' => now(),
            ]);
            $u->save();

            if (!empty($data['role'])) {
                $u->syncRoles([$data['role']]);
            }

            $u->load('roles');

            \App\Services\AuditLogger::log('service_account.created', $u, [
                'created_by' => $actor->public_id,
                'roles'      => $u->roles->pluck('name')->values()->all(),
            ]);

            return $u;
        });

        return $this->success(new ServiceAccountResource($serviceAccount), 201);
    }

    public function show(User $serviceAccount)
    {
        $serviceAccount->load('roles');
        return $this->success(new ServiceAccountResource($serviceAccount));
    }

    public function update(UpdateServiceAccountRequest $request, User $serviceAccount)
    {
        $data = $request->validated();
        $originalKeys = array_keys($data); // Save keys for auditing before they are unset

        DB::transaction(function () use ($serviceAccount, $data, $originalKeys) {
            $oldStatus = $serviceAccount->status;

            // Protecciones: nunca permitir type/email/password aquí (controlado server-side)
            unset($data['type'], $data['email'], $data['password']);

            if (array_key_exists('description', $data)) {
                $meta = $serviceAccount->meta ?? [];
                $meta['description'] = $data['description'];
                $serviceAccount->meta = $meta;
                unset($data['description']);
            }

            $serviceAccount->fill($data);

            // Mantener suspended_at consistente como AdminUserController
            if (array_key_exists('status', $data) && $data['status'] !== $oldStatus) {
                $serviceAccount->suspended_at = $data['status'] === 'suspended' ? now() : null;
            }

            $serviceAccount->save();

            if (array_key_exists('role', $data)) {
                $before = $serviceAccount->roles()->pluck('name')->sort()->values()->all();

                $role = $data['role'];
                $role ? $serviceAccount->syncRoles([$role]) : $serviceAccount->syncRoles([]);

                $after = $serviceAccount->roles()->pluck('name')->sort()->values()->all();

                if ($before !== $after) {
                    \App\Services\AuditLogger::log('service_account.role_changed', $serviceAccount, [
                        'from' => $before,
                        'to'   => $after,
                    ]);
                }
            }

            $serviceAccount->load('roles');

            $updatedFields = array_diff($originalKeys, ['status', 'role']);
            if (!empty($updatedFields)) {
                \App\Services\AuditLogger::log('service_account.updated', $serviceAccount, [
                    'fields' => array_values($updatedFields),
                ]);
            }
        });

        $serviceAccount->load('roles');
        return $this->success(new ServiceAccountResource($serviceAccount));
    }
}
