<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventLocation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLocationsTest extends TestCase
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

    public function test_master_can_create_location_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/locations", [
                'name' => 'Salón principal',
                'address' => 'Av. Principal 123',
                'maps_url' => 'https://maps.example.com/location',
                'type' => 'reception',
                'display_order' => 0,
                'is_enabled' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Salón principal')
            ->assertJsonPath('data.type', 'reception');

        $this->assertDatabaseHas('event_locations', [
            'event_id' => $event->id,
            'name' => 'Salón principal',
        ]);
    }

    public function test_master_can_list_own_event_locations(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);

        EventLocation::factory()->count(2)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/locations");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_master_cannot_access_other_owner_event_locations(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1 = $this->loginToken($master1);
        $event2 = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/locations");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_master_can_update_location_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);
        $location = EventLocation::factory()->create([
            'event_id' => $event->id,
            'name' => 'Ubicación inicial',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/locations/{$location->public_id}", [
                'name' => 'Ubicación actualizada',
                'display_order' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Ubicación actualizada')
            ->assertJsonPath('data.display_order', 2);
    }

    public function test_master_can_delete_location_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);
        $location = EventLocation::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/locations/{$location->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('event_locations', ['id' => $location->id]);
    }

    public function test_unauthenticated_cannot_create_location(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/v1/events/{$event->slug}/locations", [
            'name' => 'Sin auth',
        ]);

        $response->assertStatus(401);
    }
}
