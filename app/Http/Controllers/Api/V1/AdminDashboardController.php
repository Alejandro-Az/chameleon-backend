<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\User;
use App\Traits\HasApiResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use HasApiResponse;

    public function __invoke(Request $request)
    {
        $user = $request->user();

        $data = [
            'metrics' => [],
            'recent_activity' => [],
        ];

        // 1. Users & Roles Metrics (Requires admin.users.manage or admin.roles.manage)
        if ($user->can('admin.users.manage')) {
            $userMetrics = User::human()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
                ->selectRaw("SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended")
                ->first();

            $data['metrics']['users'] = [
                'total' => (int) $userMetrics->total,
                'active' => (int) $userMetrics->active,
                'suspended' => (int) $userMetrics->suspended,
            ];
        }

        if ($user->can('admin.roles.manage')) {
            $data['metrics']['roles'] = Role::where('guard_name', 'api')->count();
        }

        // 2. Security Metrics (Requires admin.security.view)
        if (config('kaan.features.admin_security', true) && $user->can('admin.security.view')) {
            $data['metrics']['active_sessions'] = AuthSession::whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count();

            if (config('kaan.features.login_attempts', true)) {
                $data['metrics']['failed_logins_24h'] = LoginAttempt::where('status', 'failed')
                    ->where('created_at', '>=', now()->subDay())
                    ->count();

                $attempts = LoginAttempt::latest()->limit(5)->get();
                $data['recent_activity']['login_attempts'] = \App\Http\Resources\LoginAttemptResource::collection($attempts)->resolve();
            }
        }

        // 3. Audit Logs (Requires admin.audit.view)
        if (config('kaan.features.audit', true) && $user->can('admin.audit.view')) {
            $logs = AuditLog::with('user')->latest()->limit(5)->get();
            $data['recent_activity']['audit_logs'] = \App\Http\Resources\AuditLogResource::collection($logs)->resolve();
        }

        return $this->success($data);
    }
}
