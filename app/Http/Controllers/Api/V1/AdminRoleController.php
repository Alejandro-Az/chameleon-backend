<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(Request $request)
    {
        $q = Role::where('guard_name', 'api');

        if ($search = trim((string)$request->query('q', ''))) {
            $q->where('name', 'like', "%{$search}%");
        }

        $perPage = $this->resolvePerPage($request);
        $roles = $q->with('permissions')->latest()->paginate($perPage);

        $payload = RoleResource::collection($roles)->response()->getData(true);

        return $this->success($payload);
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();
        
        // Forzar guard_name api si no se envía o para seguridad
        $data['guard_name'] = 'api';

        $role = Role::create($data);

        \App\Services\AuditLogger::log('role.created', $role, $data);

        return $this->success(new RoleResource($role), 201);
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        return $this->success(new RoleResource($role));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();
        $role->update($data);

        \App\Services\AuditLogger::log('role.updated', $role, $data);

        return $this->success(new RoleResource($role));
    }

    public function destroy(Role $role)
    {
        // Evitar borrar rol admin por seguridad
        if ($role->name === 'admin') {
            return $this->error('AUTH_FORBIDDEN', 'No se puede eliminar el rol de administrador.', 403);
        }

        $role->delete();

        \App\Services\AuditLogger::log('role.deleted', $role);

        return $this->success(['message' => 'Rol eliminado correctamente.']);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role)
    {
        // Evitar quitar permisos críticos al admin (opcional, pero recomendado)
        // if ($role->name === 'admin') { ... } 

        $permissions = $request->input('permissions');
        $role->syncPermissions($permissions);
        $role->load('permissions');

        \App\Services\AuditLogger::log('role.permissions_synced', $role, ['permissions' => $permissions]);

        return $this->success(new RoleResource($role));
    }
}
