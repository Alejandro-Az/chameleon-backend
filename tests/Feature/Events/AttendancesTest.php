<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancesTest extends TestCase
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

    // -----------------------------------------------------------------------
    // GET /api/v1/events/{slug}/attendance
    // -----------------------------------------------------------------------

    public function test_master_can_list_checked_in_guests(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        Guest::factory()->count(2)->create([
            'event_id'      => $event->id,
            'checked_in_at' => now(),
        ]);
        Guest::factory()->create([
            'event_id'      => $event->id,
            'checked_in_at' => null,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/attendance");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_master_cannot_list_attendance_of_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/attendance");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_list_attendance(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/v1/events/{$event->slug}/attendance");

        $response->assertStatus(401);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/events/{slug}/attendance/{guest}
    // -----------------------------------------------------------------------

    public function test_master_can_check_in_guest(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = Guest::factory()->create([
            'event_id'      => $event->id,
            'checked_in_at' => null,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['data' => ['id', 'checked_in_at']]);

        $this->assertNotNull($response->json('data.checked_in_at'));
        $this->assertDatabaseHas('guests', [
            'id'            => $guest->id,
        ]);
        $this->assertNotNull(Guest::find($guest->id)->checked_in_at);
    }

    public function test_check_in_already_checked_in_guest_returns_422(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = Guest::factory()->create([
            'event_id'      => $event->id,
            'checked_in_at' => now()->subMinutes(10),
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'ATTENDANCE_ALREADY_CHECKED_IN');
    }

    public function test_master_cannot_check_in_guest_of_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $guest   = Guest::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->postJson("/api/v1/events/{$event2->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_check_in_unknown_guest_returns_404(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/attendance/nonexistent-public-id");

        $response->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/v1/events/{slug}/attendance/{guest}
    // -----------------------------------------------------------------------

    public function test_master_can_revert_check_in(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = Guest::factory()->create([
            'event_id'      => $event->id,
            'checked_in_at' => now()->subMinutes(5),
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.checked_in_at', null);

        $this->assertNull(Guest::find($guest->id)->checked_in_at);
    }

    public function test_revert_check_in_on_not_checked_in_guest_returns_422(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = Guest::factory()->create([
            'event_id'      => $event->id,
            'checked_in_at' => null,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'ATTENDANCE_NOT_CHECKED_IN');
    }

    public function test_master_cannot_revert_check_in_of_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $guest   = Guest::factory()->create([
            'event_id'      => $event2->id,
            'checked_in_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->deleteJson("/api/v1/events/{$event2->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_check_in_guest(): void
    {
        $event = Event::factory()->create();
        $guest = Guest::factory()->create(['event_id' => $event->id]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/attendance/{$guest->public_id}");

        $response->assertStatus(401);
    }
}
