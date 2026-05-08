<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function loginToken(User $user): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login'    => $user->email,
            'password' => 'Secret123456',
        ]);
        return $res->json('data.access_token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeMaster(): User
    {
        /** @var User $master */
        $master = User::factory()->create([
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $master->assignRole('master');
        return $master;
    }

    private function createEventWithModules(User $master, string $token): array
    {
        $res = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', ['name' => 'Evento', 'type' => 'wedding']);
        return [$res->json('data.slug'), $res->json('data.modules')];
    }

    public function test_master_can_get_modules(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug] = $this->createEventWithModules($master, $token);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$slug}/modules");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        $modules = $response->json('data');
        $this->assertNotEmpty($modules);
    }

    public function test_master_can_update_module_order_and_enabled(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug, $modules] = $this->createEventWithModules($master, $token);

        $updated = array_map(function ($module, $index) use ($modules) {
            return [
                'module_key' => $module['module_key'],
                'enabled'    => $index !== 0,
                'order'      => count($modules) - 1 - $index,
            ];
        }, $modules, array_keys($modules));

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$slug}/modules", ['modules' => $updated]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        $getResponse = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$slug}/modules");

        $persistedModules = collect($getResponse->json('data'))->sortBy('order')->values();
        $this->assertFalse((bool) $persistedModules[count($updated) - 1]['enabled']);
    }

    public function test_module_update_validates_module_key(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug] = $this->createEventWithModules($master, $token);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$slug}/modules", [
                'modules' => [
                    ['module_key' => 'fake_module', 'enabled' => true, 'order' => 0],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $details = $response->json('error.details');
        $this->assertArrayHasKey('modules.0.module_key', $details);
    }

    public function test_non_owner_cannot_get_modules(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token2  = $this->loginToken($master2);
        $event   = Event::factory()->create(['owner_id' => $master1->id]);

        $response = $this->withHeaders($this->authHeader($token2))
            ->getJson("/api/v1/events/{$event->slug}/modules");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_modules_response_has_required_fields(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug] = $this->createEventWithModules($master, $token);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$slug}/modules");

        $response->assertJsonStructure([
            'ok',
            'data' => [['module_key', 'enabled', 'order']],
        ]);
    }
}
