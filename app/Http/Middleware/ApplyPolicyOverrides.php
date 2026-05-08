<?php

namespace App\Http\Middleware;

use App\Services\Policy\PolicyService;
use Closure;
use Illuminate\Http\Request;

class ApplyPolicyOverrides
{
    public function __construct(private readonly PolicyService $policies) {}

    public function handle(Request $request, Closure $next)
    {
        if (!config('kaan.features.policy_center', false)) {
            return $next($request); // feature disabled: no-op
        }

        $snapshot = $this->policies->getSnapshotOrNull();

        if (!$snapshot) {
            return $next($request); // degraded: no crash
        }

        $original = [];

        try {
            $apply = $snapshot['apply_map'] ?? [];

            foreach ($apply as $path => $value) {
                $original[$path] = config($path);
                config()->set($path, $value);
            }

            // Optional ETag can be read by controller; but not required here.
            return $next($request);
        } finally {
            // Octane-safe: restore config to avoid cross-request leaks
            foreach ($original as $path => $value) {
                config()->set($path, $value);
            }
        }
    }
}
