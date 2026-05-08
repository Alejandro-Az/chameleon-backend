<?php

namespace App\Services\ApiKeys;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApiKeyService
{
    public function generate(
        User $serviceAccount,
        string $name,
        array $scopes = [],
        ?\Carbon\CarbonInterface $expiresAt = null,
        ?User $createdBy = null
    ): array {
        if (($serviceAccount->type ?? 'human') !== 'service') {
            throw new \InvalidArgumentException('API keys solo se pueden crear para service accounts (type=service).');
        }

        $plaintext = $this->generatePlaintext();
        $hash = $this->hash($plaintext);

        $prefixLen = min((int) config('kaan.api_keys.prefix_length', 12), 64);
        $prefix = substr($plaintext, 0, $prefixLen);

        $apiKey = ApiKey::create([
            'user_id'            => $serviceAccount->id,
            'created_by_user_id' => $createdBy?->id,
            'name'               => $name,
            'prefix'             => $prefix,
            'key_hash'           => $hash,
            'scopes'             => $scopes,
            'expires_at'         => $expiresAt,
        ]);

        return ['api_key' => $apiKey, 'secret' => $plaintext];
    }

    public function rotate(ApiKey $apiKey, ?User $actor = null): array
    {
        return DB::transaction(function () use ($apiKey, $actor) {
            if (!$apiKey->revoked_at) {
                $apiKey->update(['revoked_at' => now()]);
            }

            return $this->generate(
                serviceAccount: $apiKey->owner,
                name: $apiKey->name,
                scopes: $apiKey->scopes ?? [],
                expiresAt: $apiKey->expires_at,
                createdBy: $actor
            );
        });
    }

    public function revoke(ApiKey $apiKey): void
    {
        if ($apiKey->revoked_at) {
            return; // idempotente
        }

        $apiKey->update(['revoked_at' => now()]);
    }

    private function generatePlaintext(): string
    {
        $prefix = (string) config('kaan.api_keys.prefix', 'kk_live_');

        // 32 bytes => 256 bits entropy, base64url safe
        $random = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return $prefix . $random;
    }

    private function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext); // hex(64)
    }
}
