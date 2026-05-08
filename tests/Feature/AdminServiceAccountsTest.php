<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\AuthSession;
use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminServiceAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function assertApiKeysFeatureEnabled(): void
    {
        $this->assertTrue(
            (bool) config('kaan.features.api_keys', false),
            'KAAN_FEATURE_API_KEYS debe estar en true para correr PR3 tests (rutas gated). Agrega <env name="KAAN_FEATURE_API_KEYS" value="true"/> a phpunit.xml.'
        );
    }

    private function loginToken(User $user, string $password = 'Secret123456'): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => $password,
        ]);

        $res->assertOk()->assertJsonPath('ok', true);

        $token = $res->json('data.access_token');
        $this->assertIsString($token);

        return $token;
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeAdmin(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@demo.kaanforge.test',
            'username' => 'admin',
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        $this->assertTrue(
            $admin->can('admin.service_accounts.manage'),
            'Admin debe tener permiso admin.service_accounts.manage (revisa RolesAndPermissionsSeeder: guard api + syncPermissions).'
        );

        return $admin;
    }

    public function test_index_requires_auth(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $this->getJson('/api/v1/admin/service-accounts')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_index_requires_permission(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $user = User::factory()->create([
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $token = $this->loginToken($user);

        $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/service-accounts')
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_admin_can_create_list_show_and_update_service_account(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        // Crea un humano normal para asegurar que el listado solo incluye service
        User::factory()->create([
            'name' => 'Human',
            'email' => 'human@test.com',
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
            // type default = human por migración
        ]);

        // CREATE
        $create = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/admin/service-accounts', [
                'name' => 'Integración - ERP Sync',
                'email' => 'svc-erp@service.local',
                'description' => 'Cuenta para cron nightly',
                'role' => 'user',
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => ['id', 'type', 'name', 'email', 'status'],
            ])
            ->assertJsonPath('data.type', 'service')
            ->assertJsonPath('data.email', 'svc-erp@service.local');

        $serviceAccountId = $create->json('data.id');
        $this->assertIsString($serviceAccountId);

        // LIST
        $list = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/service-accounts?per_page=15');

        $list->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => ['data', 'links', 'meta'],
            ]);

        $items = $list->json('data.data');
        $this->assertIsArray($items);

        foreach ($items as $item) {
            $this->assertEquals('service', $item['type'] ?? null, 'El listado debe contener solo service accounts (type=service).');
        }

        // SHOW
        $show = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/service-accounts/{$serviceAccountId}");

        $show->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.id', $serviceAccountId)
            ->assertJsonPath('data.type', 'service');

        // UPDATE (PATCH)
        $update = $this->withHeaders($this->authHeader($token))
            ->patchJson("/api/v1/admin/service-accounts/{$serviceAccountId}", [
                'name' => 'Integración - ERP Sync (Updated)',
                'status' => 'suspended',
                'description' => 'Cuenta pausada por mantenimiento',
                'role' => 'user',
            ]);

        $update->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Integración - ERP Sync (Updated)')
            ->assertJsonPath('data.status', 'suspended');

        // DB: status + suspended_at consistente
        $this->assertDatabaseHas('users', [
            'public_id' => $serviceAccountId,
            'status' => 'suspended',
        ]);

        /** @var User $svc */
        $svc = User::query()->where('public_id', $serviceAccountId)->firstOrFail();
        $this->assertNotNull($svc->suspended_at, 'Al suspender un service account, suspended_at debe setearse.');
        $this->assertEquals('service', $svc->type);
    }

    public function test_suspending_service_account_revokes_api_keys_and_sessions(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        // Crear service account vía API (así probamos todo el flujo real)
        $create = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/admin/service-accounts', [
                'name' => 'Svc - Marketing Bot',
                'email' => 'svc-marketing@service.local',
                'role' => 'user',
            ]);

        $create->assertStatus(201)->assertJsonPath('ok', true);
        $serviceAccountId = $create->json('data.id');

        /** @var User $svc */
        $svc = User::query()->where('public_id', $serviceAccountId)->firstOrFail();

        // Crear 2 API keys directamente con el service (más rápido y determinista)
        /** @var ApiKeyService $svcKeyService */
        $svcKeyService = app(ApiKeyService::class);
        $svcKeyService->generate($svc, 'Key 1');
        $svcKeyService->generate($svc, 'Key 2');

        $this->assertDatabaseHas('api_keys', ['user_id' => $svc->id]);

        // Crear una sesión activa (simula token activo)
        AuthSession::create([
            'user_id' => $svc->id,
            'token_id' => Str::random(64),
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'last_seen_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Suspender (debe disparar UserObserver: revoke sessions + revoke api keys)
        $update = $this->withHeaders($this->authHeader($token))
            ->patchJson("/api/v1/admin/service-accounts/{$serviceAccountId}", [
                'status' => 'suspended',
            ]);

        $update->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', 'suspended');

        // Assert: todas las api_keys del service account están revocadas
        $this->assertTrue(
            ApiKey::query()->where('user_id', $svc->id)->whereNull('revoked_at')->count() === 0,
            'Al suspender un service account, no deben quedar API keys activas.'
        );

        // Assert: sesiones revocadas (UserObserver ya revoca por status change)
        $this->assertDatabaseHas('auth_sessions', [
            'user_id' => $svc->id,
        ]);

        $this->assertTrue(
            AuthSession::query()
                ->where('user_id', $svc->id)
                ->where('expires_at', '>', now())
                ->whereNull('revoked_at')
                ->count() === 0,
            'Al suspender, no deben quedar sesiones activas.'
        );
    }
}
