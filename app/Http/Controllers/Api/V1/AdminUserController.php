<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(Request $request)
    {
        $q = User::with('roles')->human();

        if ($search = trim((string)$request->query('q', ''))) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $perPage = $this->resolvePerPage($request);

        $page = $q->latest()->paginate($perPage);

        // 👇 Clave: convertir a array "paginado" completo
        $payload = UserResource::collection($page)->response()->getData(true);

        return $this->success($payload);
    }


    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            // public_id se autogenera en booted(), pero por seguridad:
            $data['public_id'] = $data['public_id'] ?? (string) Str::ulid();

            /** @var User $user */
            $user = User::create($data);

            // Forzar type = human server-side (no es fillable)
            $user->type = 'human';
            $user->save();

            // opcional: asignar rol por request
            if (!empty($data['role'])) {
                $user->syncRoles([$data['role']]);
                $user->load('roles');
                $afterRoles = $user->roles()->pluck('name')->sort()->values()->all();
                
                \App\Services\AuditLogger::log('user.role_changed', $user, [
                    'from' => [],
                    'to' => $afterRoles
                ]);
            } else {
                $user->load('roles');
            }

            \App\Services\AuditLogger::log('user.created', $user, ['email' => $user->email]);

            return $user;
        });

        return $this->success(new UserResource($user), 201);
    }

    public function show(User $user)
    {
        if ($user->type === 'service') {
            return $this->error('RESOURCE_NOT_FOUND', 'Este recurso pertenece al módulo de Service Accounts.', 404);
        }

        $user->load('roles');
        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        if ($user->type === 'service') {
            return $this->error('RESOURCE_NOT_FOUND', 'Este recurso pertenece al módulo de Service Accounts.', 404);
        }

        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $oldStatus = $user->status;

            $user->fill($data);

            // Traceability: maintain suspended_at
            if (array_key_exists('status', $data) && $data['status'] !== $oldStatus) {
                $user->suspended_at = $data['status'] === 'suspended' ? now() : null;
            }

            $user->save();

            // Session revocation + user.status_changed audit are handled by UserObserver

            if (array_key_exists('role', $data)) {
                $beforeRoles = $user->roles()->pluck('name')->sort()->values()->all();
                
                $role = $data['role'];
                $role ? $user->syncRoles([$role]) : $user->syncRoles([]);
                
                $afterRoles = $user->roles()->pluck('name')->sort()->values()->all();

                if ($beforeRoles !== $afterRoles) {
                    \App\Services\AuditLogger::log('user.role_changed', $user, [
                        'from' => $beforeRoles,
                        'to' => $afterRoles
                    ]);
                }
            }
            
            $user->load('roles');

            $updatedFields = array_diff(array_keys($data), ['status', 'role']);
            if (!empty($updatedFields)) {
                \App\Services\AuditLogger::log('user.updated', $user, ['fields' => array_values($updatedFields)]);
            }
        });

        return $this->success(new UserResource($user));
    }


    public function destroy(User $user)
    {
        if ($user->type === 'service') {
            return $this->error('RESOURCE_NOT_FOUND', 'Este recurso pertenece al módulo de Service Accounts.', 404);
        }

        $user->delete(); // soft delete

        \App\Services\AuditLogger::log('user.deleted', $user);

        return $this->success(['message' => 'Usuario eliminado correctamente.']);
    }
}
