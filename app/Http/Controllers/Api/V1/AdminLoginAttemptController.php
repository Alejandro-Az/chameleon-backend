<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListLoginAttemptsRequest;
use App\Http\Resources\LoginAttemptResource;
use App\Models\LoginAttempt;

class AdminLoginAttemptController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(ListLoginAttemptsRequest $request)
    {
        $perPage = $this->resolvePerPage($request, $request->validated('per_page'));

        $query = LoginAttempt::query()->orderBy('created_at', 'desc');

        if ($q = $request->validated('q')) {
            $query->where('email', 'like', "%{$q}%");
        }

        if ($ip = $request->validated('ip')) {
            $query->where('ip_address', $ip);
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->validated('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->validated('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $page = $query->paginate($perPage);

        $payload = LoginAttemptResource::collection($page)->response()->getData(true);

        return $this->success($payload);
    }
}
