<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventDressCode;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDressCodesTest extends TestCase
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
            'login' => $user->email,
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
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $master->assignRole('master');

        return $master;
    }

    public function test_master_can_create_dress_code_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/dress-codes", [
                'title' => 'Formal elegante',
                'description' => 'Vestimenta formal para la ceremonia',
                'examples' => 'Traje oscuro, vestido largo',
                'notes' => 'Evitar tenis y mezclilla',
                'display_order' => 0,
                'is_enabled' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Formal elegante');

        $this->assertDatabaseHas('event_dress_codes', [
            'event_id' => $event->id,
            'title' => 'Formal elegante',
        ]);
    }

    public function test_master_can_list_own_event_dress_codes(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);

        EventDressCode::factory()->count(2)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/dress-codes");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_master_cannot_access_other_owner_event_dress_codes(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1 = $this->loginToken($master1);
        $event2 = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/dress-codes");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_master_can_update_dress_code_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);
        $dressCode = EventDressCode::factory()->create([
            'event_id' => $event->id,
            'title' => 'Código inicial',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/dress-codes/{$dressCode->public_id}", [
                'title' => 'Código actualizado',
                'display_order' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Código actualizado')
            ->assertJsonPath('data.display_order', 1);
    }

    public function test_master_can_delete_dress_code_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);
        $dressCode = EventDressCode::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/dress-codes/{$dressCode->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('event_dress_codes', ['id' => $dressCode->id]);
    }

    public function test_unauthenticated_cannot_create_dress_code(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/v1/events/{$event->slug}/dress-codes", [
            'title' => 'Sin auth',
        ]);

        $response->assertStatus(401);
    }
}
