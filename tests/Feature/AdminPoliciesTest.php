<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class AdminPoliciesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $guard = 'api';
        Permission::firstOrCreate(['name' => 'admin.policies.view', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'admin.policies.manage', 'guard_name' => $guard]);
        
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $role->givePermissionTo('admin.policies.view', 'admin.policies.manage');

        $this->admin = User::factory()->create([
            'password' => 'Secret123456',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');

        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);
        
        $this->user = User::factory()->create([
            'password' => 'Secret123456',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('user'); // Does not have policies permissions

        Cache::clear();
    }

    private function getAuthHeader(User $user): array
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'Secret123456',
        ]);
        return ['Authorization' => 'Bearer ' . $res->json('data.access_token')];
    }

    public function test_requires_auth()
    {
        $this->getJson('/api/v1/admin/policies')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED');
    }

    public function test_requires_permission()
    {
        $this->withHeaders($this->getAuthHeader($this->user))
            ->getJson('/api/v1/admin/policies')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_list_and_show()
    {
        $res = $this->withHeaders($this->getAuthHeader($this->admin))
            ->getJson('/api/v1/admin/policies')
            ->assertStatus(200);

        $version = $res->json('data.policy_version');
        $this->assertIsInt($version);

        $res->assertHeader('ETag', 'W/"policy-v' . $version . '"');

        $this->withHeaders($this->getAuthHeader($this->admin))
            ->getJson('/api/v1/admin/policies/api.pagination.max_per_page')
            ->assertStatus(200)
            ->assertJsonPath('data.policy.key', 'api.pagination.max_per_page');
    }

    public function test_patch_conflict()
    {
        $this->withHeaders($this->getAuthHeader($this->admin))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'api.pagination.default_per_page' => 50, 
                    'api.pagination.max_per_page' => 40,
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'POLICY_CONFLICT');
    }

    public function test_patch_read_only()
    {
        $this->withHeaders($this->getAuthHeader($this->admin))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'features.api_keys' => true,
                ],
            ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'POLICY_READ_ONLY');
    }

    public function test_patch_unknown_key()
    {
        $this->withHeaders($this->getAuthHeader($this->admin))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'non.existent' => 'value',
                ],
            ])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'POLICY_NOT_FOUND');
    }

    public function test_patch_success()
    {
        $this->withHeaders($this->getAuthHeader($this->admin))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'api.pagination.max_per_page' => 50,
                ],
            ])
            ->assertStatus(200);
            
        $this->withHeaders($this->getAuthHeader($this->admin))
            ->getJson('/api/v1/admin/policies/api.pagination.max_per_page')
            ->assertStatus(200)
            ->assertJsonPath('data.policy.value', 50);
    }

    public function test_patch_per_key_rules_invalid_returns_validation_error()
    {
        $this->withHeaders($this->getAuthHeader($this->admin))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'api.pagination.default_per_page' => 0, // Rule min:1 should fail
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details', null);
    }

    public function test_degraded_mode_redacts_sensitive_values()
    {
        // Add a fake sensitive policy to the registry for the test
        config(['kaan_policies.test_sensitive_key' => [
            'type' => 'string',
            'sensitive' => true,
            'editable' => true,
            'default' => 'super-secret-fallback',
        ]]);

        // Rename the table to simulate an isolated DB failure specifically for PolicySettings
        // This causes a QueryException during buildSnapshot(), forcing degraded mode.
        \Illuminate\Support\Facades\Schema::rename('policy_settings', 'policy_settings_broken');
        
        Cache::clear(); // Enforce hitting the DB

        try {
            $res = $this->withHeaders($this->getAuthHeader($this->admin))
                ->getJson('/api/v1/admin/policies');

            $res->assertStatus(200)
                ->assertJsonPath('data.degraded', true);

            $groups = $res->json('data.groups');
            $found = null;
            foreach ($groups as $group) {
                foreach ($group as $p) {
                    if ($p['key'] === 'test_sensitive_key') {
                        $found = $p;
                        break 2;
                    }
                }
            }
            
            $this->assertNotNull($found, 'Sensitive policy not found in degraded list');
            $this->assertNull($found['value']);
            $this->assertTrue($found['meta']['redacted']);

            // Also verify the show() method properly degrades and redacts
            $resShow = $this->withHeaders($this->getAuthHeader($this->admin))
                ->getJson('/api/v1/admin/policies/test_sensitive_key');

            $resShow->assertStatus(200)
                ->assertJsonPath('data.policy.value', null)
                ->assertJsonPath('data.policy.meta.redacted', true);
        } finally {
            // Restore the table immediately so other tests or RefreshDatabase don't break
            \Illuminate\Support\Facades\Schema::rename('policy_settings_broken', 'policy_settings');
        }
    }

    public function test_persists_sensitive_values_encrypted()
    {
        // First, add a fake sensitive policy to the registry for the test
        config(['kaan_policies.test_sensitive_key' => [
            'type' => 'string',
            'sensitive' => true,
            'editable' => true,
            'default' => 'secret',
        ]]);

        $this->withHeaders($this->getAuthHeader($this->admin))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'test_sensitive_key' => 'super-secret-value',
                ],
            ])
            ->assertStatus(200);

        $setting = \App\Models\PolicySetting::where('key', 'test_sensitive_key')->first();
        
        $this->assertNotNull($setting);
        $this->assertNull($setting->value_json);
        $this->assertNotNull($setting->value_encrypted);
        
        // Assert it was encrypted using Crypt
        $this->assertSame('super-secret-value', json_decode(\Illuminate\Support\Facades\Crypt::decryptString($setting->value_encrypted), true));
    }

    public function test_run_with_config_overrides_restores_on_exception()
    {
        $service = app(\App\Services\Policy\PolicyService::class);
        
        config(['kaan.pagination.max_per_page' => 100]); // default
        
        // Insert a db override
        \App\Models\PolicySetting::create([
            'key' => 'api.pagination.max_per_page',
            'value_json' => 200,
            'value_encrypted' => null,
        ]);
        
        Cache::clear(); // Force snapshot reload

        try {
            $service->runWithConfigOverrides(function () {
                $this->assertEquals(200, config('kaan.pagination.max_per_page'));
                throw new \RuntimeException('Worker crashed');
            });
        } catch (\RuntimeException $e) {
            $this->assertEquals('Worker crashed', $e->getMessage());
        }

        // Must be restored
        $this->assertEquals(100, config('kaan.pagination.max_per_page'));
    }
}
