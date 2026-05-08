<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class AdminPermissionController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(Request $request)
    {
        $q = Permission::query();

        $availableGuards = array_keys(config('auth.guards', []));

        $validated = $request->validate([
            'guard_name' => ['nullable', 'string', Rule::in($availableGuards)],
        ]);

        $rawGuard = $validated['guard_name'] ?? null;

        // Normalize default guard: avoid empty string and ensure it is in the whitelist
        $defaultGuard = config('auth.defaults.guard');
        $defaultGuard = $defaultGuard ?: 'api';
        if (!in_array($defaultGuard, $availableGuards, true) && !empty($availableGuards)) {
            $defaultGuard = reset($availableGuards);
        }

        // Derive final guard from request or normalized default, keeping it within the whitelist
        $guard = $rawGuard !== null ? trim((string) $rawGuard) : $defaultGuard;
        if ($guard === '' || !in_array($guard, $availableGuards, true)) {
            $guard = $defaultGuard;
        }

        $q->where('guard_name', $guard);

        if ($search = trim((string) $request->query('q', ''))) {
            $q->where('name', 'like', "%{$search}%");
        }

        $perPage = $this->resolvePerPage($request);
        $permissions = $q->orderBy('name')->paginate($perPage);

        $payload = PermissionResource::collection($permissions)->response()->getData(true);

        return $this->success($payload);
    }
}
