<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health — always active
    require __DIR__ . '/api/v1/health.php';

    // Auth — always active
    require __DIR__ . '/api/v1/auth.php';

    if (config('kaan.features.api_keys', false)) {
        require __DIR__ . '/api/v1/auth.api-keys.php';
    }

    if (config('kaan.features.appointments', false)) {
        require __DIR__ . '/api/v1/appointments.php';
    }

    if (config('kaan.features.events', true)) {
        require __DIR__ . '/api/v1/events.php';
        require __DIR__ . '/api/v1/admin.templates.php';
    }

    // Admin modules — gated by features
    if (config('kaan.features.admin', true)) {
        require __DIR__ . '/api/v1/admin.dashboard.php';
        require __DIR__ . '/api/v1/admin.users.php';
        require __DIR__ . '/api/v1/admin.roles.php';
        require __DIR__ . '/api/v1/admin.permissions.php';

        if (config('kaan.features.api_keys', false)) {
            require __DIR__ . '/api/v1/admin.service-accounts.php';
            require __DIR__ . '/api/v1/admin.api-keys.php';
        }
        
        if (config('kaan.features.audit', true)) {
            require __DIR__ . '/api/v1/admin.audit-logs.php';
        }

        if (config('kaan.features.admin_security', true)) {
            require __DIR__ . '/api/v1/admin.security.php';
        }

        if (config('kaan.features.policy_center', false)) {
            require __DIR__ . '/api/v1/admin.policies.php';
        }

        if (config('kaan.features.appointments', false)) {
            require __DIR__ . '/api/v1/admin.appointments.php';
        }
    }
});
