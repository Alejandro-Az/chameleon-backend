<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class LoginAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $exposeRaw = config('kaan.security.login_attempts.expose_raw_identifier_to_admin', false);

        return [
            // Numeric id exposed intentionally: informational/read-only resource, not addressable.
            // Frontend should not use this id for routing or mutations.
            'id'            => $this->id,
            'login_masked'  => self::maskLogin($this->email),
            'login_raw'     => $exposeRaw ? $this->email : null,
            'status'        => $this->status,
            'ip_address'    => $this->ip_address,
            'user_agent'    => $this->user_agent ? Str::limit($this->user_agent, 120) : null,
            'blocked_until' => $this->blocked_until?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Stable masking algorithm:
     * - Email: first char + *** + last char of local + @domain
     * - Non-email: first char + *** + last char
     */
    public static function maskLogin(string $login): string
    {
        if (str_contains($login, '@')) {
            [$local, $domain] = explode('@', $login, 2);
            $maskedLocal = self::maskPart($local);
            return "{$maskedLocal}@{$domain}";
        }

        return self::maskPart($login);
    }

    private static function maskPart(string $part): string
    {
        $len = mb_strlen($part);

        if ($len <= 1) {
            return '***';
        }

        if ($len === 2) {
            return mb_substr($part, 0, 1) . '***';
        }

        // >= 3 chars: first + *** + last
        return mb_substr($part, 0, 1) . '***' . mb_substr($part, -1);
    }
}
