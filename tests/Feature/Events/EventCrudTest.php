<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TemplatesSeeder::class);
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

    public function test_master_can_create_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', [
                'name' => 'Boda de Sofía y Mateo',
                'type' => 'wedding',
                'date' => now()->addMonths(3)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Boda de Sofía y Mateo')
            ->assertJsonPath('data.type', 'wedding')
            ->assertJsonPath('data.status', 'draft');

        $this->assertNotNull($response->json('data.slug'));
        $this->assertNotNull($response->json('data.modules'));
    }

    public function test_event_modules_initialized_on_create(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', [
                'name' => 'XV de Valentina',
                'type' => 'quinceanera',
            ]);

        $response->assertStatus(201);
        $modules = $response->json('data.modules');

        $this->assertNotEmpty($modules);
        $moduleKeys = array_column($modules, 'module_key');
        $this->assertContains('rsvp', $moduleKeys);
        $this->assertContains('gifts', $moduleKeys);
    }

    public function test_public_can_view_published_event(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->published()->create(['owner_id' => $master->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.slug', $event->slug);
    }

    public function test_public_can_view_draft_event(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id, 'status' => 'draft']);

        $response = $this->getJson("/api/v1/events/{$event->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);
    }

    public function test_master_can_list_own_events(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        Event::factory()->count(3)->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_master_cannot_see_other_masters_events(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        Event::factory()->count(2)->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_master_can_update_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}", ['name' => 'Nombre actualizado']);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Nombre actualizado');
    }

    public function test_master_cannot_update_other_masters_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event   = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->putJson("/api/v1/events/{$event->slug}", ['name' => 'Hack']);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_master_can_delete_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}");

        $response->assertStatus(200)->assertJsonPath('ok', true);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_unauthenticated_cannot_create_event(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'name' => 'Sin auth',
            'type' => 'wedding',
        ]);

        $response->assertStatus(401);
    }

    public function test_event_response_has_required_fields(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);

        $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', ['name' => 'Evento', 'type' => 'party']);

        $slug = Event::where('owner_id', $master->id)->first()->slug;

        $response = $this->getJson("/api/v1/events/{$slug}");
        $response->assertJsonStructure([
            'ok',
            'data' => ['slug', 'name', 'type', 'status', 'modules'],
        ]);
    }
}
