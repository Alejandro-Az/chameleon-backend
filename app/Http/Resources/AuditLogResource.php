<?php

namespace App\Http\Resources;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Numeric id exposed intentionally: informational/read-only resource, not addressable.
            // Frontend should not use this id for routing or mutations.
            'id' => $this->id,
            'action' => $this->action,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->public_id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'model' => [
                'type' => $this->model_type,
                'id' => $this->model_id,
            ],
            'details' => $this->sanitizeDetails($this->details),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Recursively sanitizes the details array with depth limiting.
     * Uses the unified SENSITIVE_KEYS from AuditLogger.
     */
    protected function sanitizeDetails(mixed $data, int $depth = 0): mixed
    {
        // Limit recursion depth to prevent stack overflows on malicious/massive payloads
        if ($depth > 10) {
            return '[MAX_DEPTH_REACHED]';
        }

        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if ($this->shouldRedact($key)) {
                $sanitized[$key] = '[REDACTED]';
            } else if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeDetails($value, $depth + 1);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function shouldRedact(string $key): bool
    {
        $normalizedKey = strtolower($key);
        foreach (AuditLogger::SENSITIVE_KEYS as $redactKey) {
            if (str_contains($normalizedKey, strtolower($redactKey))) {
                return true;
            }
        }
        return false;
    }
}
