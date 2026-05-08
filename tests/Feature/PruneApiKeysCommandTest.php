<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneApiKeysCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeServiceAccount(): User
    {
        /** @var User $svc */
        $svc = User::factory()->create([
            'name' => 'Svc - Test',
            'email' => 'svc-test@service.local',
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $svc->type = 'service';
        $svc->save();

        return $svc;
    }

    public function test_prune_command_aborts_if_feature_is_disabled(): void
    {
        config(['kaan.features.api_keys' => false]);

        $this->artisan('kaan:prune-api-keys')
            ->expectsOutput('API Keys feature is disabled. Skipping pruning.')
            ->assertSuccessful();
    }

    public function test_prune_command_deletes_expired_and_revoked_keys_outside_retention_window(): void
    {
        config(['kaan.features.api_keys' => true]);
        config(['kaan.api_keys.retention_days' => 30]);

        $svc = $this->makeServiceAccount();

        // 1. Key revocada hace 31 días (DEBE BORRARSE)
        $keyA = ApiKey::factory()->create([
            'user_id' => $svc->id,
            'revoked_at' => now()->subDays(31),
        ]);

        // 2. Key expirada hace 31 días (DEBE BORRARSE)
        $keyB = ApiKey::factory()->create([
            'user_id' => $svc->id,
            'expires_at' => now()->subDays(31),
        ]);

        // 3. Key revocada recientemente (hace 10 días) (NO DEBE BORRARSE)
        $keyC = ApiKey::factory()->create([
            'user_id' => $svc->id,
            'revoked_at' => now()->subDays(10),
        ]);

        // 4. Key expirada recientemente (hace 10 días) (NO DEBE BORRARSE)
        $keyD = ApiKey::factory()->create([
            'user_id' => $svc->id,
            'expires_at' => now()->subDays(10),
        ]);

        // 5. Key activa (nunca expira, no revocada) (NO DEBE BORRARSE)
        $keyE = ApiKey::factory()->create([
            'user_id' => $svc->id,
            'expires_at' => null,
            'revoked_at' => null,
        ]);

        $this->artisan('kaan:prune-api-keys')
            ->expectsOutput('Successfully pruned 2 old API key(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('api_keys', ['id' => $keyA->id]);
        $this->assertDatabaseMissing('api_keys', ['id' => $keyB->id]);

        $this->assertDatabaseHas('api_keys', ['id' => $keyC->id]);
        $this->assertDatabaseHas('api_keys', ['id' => $keyD->id]);
        $this->assertDatabaseHas('api_keys', ['id' => $keyE->id]);
    }

    public function test_prune_command_supports_dry_run(): void
    {
        config(['kaan.features.api_keys' => true]);
        config(['kaan.api_keys.retention_days' => 30]);

        $svc = $this->makeServiceAccount();

        // 1. Key revocada hace 31 días (DEBE BORRARSE SI NO FUERA DRY RUN)
        $keyA = ApiKey::factory()->create([
            'user_id' => $svc->id,
            'revoked_at' => now()->subDays(31),
        ]);

        $this->artisan('kaan:prune-api-keys', ['--dry-run' => true])
            ->expectsOutput('[DRY RUN] 1 API key(s) ready to be pruned (older than 30 days).')
            ->assertSuccessful();

        // Check that the key still exists in the DB
        $this->assertDatabaseHas('api_keys', ['id' => $keyA->id]);
    }
}
