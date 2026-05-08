<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAuditLogsRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AdminAuditLogController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(ListAuditLogsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = $this->resolvePerPage($request, $validated['per_page'] ?? null);

        $query = AuditLog::query()->with('user');

        // Filter by user ULID
        if (!empty($validated['user'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('public_id', $validated['user']);
            });
        }

        // Filter by exact action
        if (!empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        // Filter by date range
        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            // End of day to include the entire 'to' date
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        // Generic search in action or user's name/email
        if (!empty($validated['q'])) {
            $search = '%' . $validated['q'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', $search)
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', $search)
                                ->orWhere('email', 'LIKE', $search);
                  });
            });
        }

        $query->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);
        $payload = AuditLogResource::collection($paginator)->response()->getData(true);

        return $this->success($payload);
    }
}
