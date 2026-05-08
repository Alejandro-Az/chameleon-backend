<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    use \App\Traits\HasApiResponse;

    public function __invoke(\App\Services\Policy\PolicyService $policies)
    {
        $dbOk = false;

        try {
            DB::select('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            // DB is down — we still return 200 for observability
        }

        $policyEnabled = (bool) config('kaan.features.policy_center', false);

        $policyDegraded = false;
        if ($policyEnabled) {
            try {
                $policyDegraded = $policies->getSnapshotOrNull() === null;
            } catch (\Throwable $e) {
                $policyDegraded = true;
            }
        }

        $serviceAccountsEnabled = (bool) config('kaan.features.api_keys', false);
        $appointmentsEnabled = (bool) config('kaan.features.appointments', false);

        return $this->success([
            'app'                       => config('kaan.name', 'Kaan Core Backend'),
            'version'                   => config('kaan.version', '0.2.0-alpha'),
            'db'                        => $dbOk,
            'policy_center_enabled'     => $policyEnabled,
            'policy_center_degraded'    => $policyDegraded,
            'service_accounts_enabled'  => $serviceAccountsEnabled,
            'appointments_enabled'      => $appointmentsEnabled,
            'time'                      => now()->toIso8601String(),
        ]);
    }
}
