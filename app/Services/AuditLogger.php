<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Keys to automatically redact from audit details payloads.
     * Shared by AuditLogger (write-time) and AuditLogResource (read-time).
     */
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'authorization',
        'cookie',
        'bearer',
        'reset_token',
        'signature',
        'api_key',
    ];

    public static function log(string $action, $model = null, ?array $details = null, $user = null)
    {
        // No-op if audit feature is disabled
        if (!config('kaan.features.audit', true)) {
            return null;
        }

        $userId = $user ? $user->id : (Auth::guard('api')->id() ?? Auth::id());

        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'details' => self::sanitizeDetails($details),
        ];

        if ($model) {
            $logData['model_type'] = get_class($model);
            $logData['model_id'] = $model->id;
        }

        return AuditLog::create($logData);
    }

    protected static function sanitizeDetails(mixed $data, int $depth = 0): mixed
    {
        if ($depth > 10) return '[MAX_DEPTH_REACHED]';
        if (!is_array($data)) return $data;

        $sanitized = [];

        foreach ($data as $key => $value) {
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
                if (stripos((string)$key, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } else if (is_array($value)) {
                $sanitized[$key] = self::sanitizeDetails($value, $depth + 1);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
