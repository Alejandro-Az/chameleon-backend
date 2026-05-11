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

class RsvpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TemplatesSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Guest management (master endpoints)
    // -------------------------------------------------------------------------

    public function test_master_can_create_guest_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/guests", [
                'name'            => 'Ana García',
                'email'           => 'ana@example.com',
                'invitation_code' => 'ANACODE01',
                'invited_seats'   => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Ana García')
            ->assertJsonPath('data.invitation_code', 'ANACODE01')
            ->assertJsonPath('data.invited_seats', 2)
            ->assertJsonPath('data.rsvp_status', 'pending');

        $this->assertDatabaseHas('guests', [
            'event_id'        => $event->id,
            'invitation_code' => 'ANACODE01',
        ]);
    }

    public function test_master_can_list_guests_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        Guest::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/guests");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_master_can_update_guest_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = Guest::factory()->create(['event_id' => $event->id, 'name' => 'Original']);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/guests/{$guest->public_id}", [
                'name'          => 'Nombre Editado',
                'invited_seats' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Nombre Editado')
            ->assertJsonPath('data.invited_seats', 3);
    }

    public function test_master_can_delete_guest_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = Guest::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/guests/{$guest->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('guests', ['id' => $guest->id]);
    }

    public function test_master_cannot_manage_guests_of_another_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/guests");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_list_guests(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/v1/events/{$event->slug}/guests")
            ->assertStatus(401);
    }

    public function test_create_guest_requires_name_and_invitation_code(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/guests", []);

        $response->assertStatus(422);
    }

    public function test_invitation_code_must_be_unique(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => 'DUPCODE01',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/guests", [
                'name'            => 'Otro invitado',
                'invitation_code' => 'DUPCODE01',
            ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Public RSVP submit endpoint
    // -------------------------------------------------------------------------

    public function test_guest_can_submit_rsvp_with_valid_invitation_code(): void
    {
        $event = Event::factory()->create();
        $guest = Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => 'MYCODE123',
            'invited_seats'   => 3,
            'rsvp_status'     => 'pending',
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/rsvp", [
            'invitation_code'  => 'MYCODE123',
            'rsvp_status'      => 'yes',
            'guests_confirmed' => 2,
            'rsvp_message'     => 'Ahí estaremos!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.rsvp_status', 'yes')
            ->assertJsonPath('data.guests_confirmed', 2)
            ->assertJsonPath('data.rsvp_message', 'Ahí estaremos!');

        $this->assertDatabaseHas('guests', [
            'id'               => $guest->id,
            'rsvp_status'      => 'yes',
            'guests_confirmed' => 2,
        ]);
    }

    public function test_rsvp_returns_404_for_unknown_invitation_code(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/v1/events/{$event->slug}/rsvp", [
            'invitation_code' => 'NONEXISTENT',
            'rsvp_status'     => 'yes',
            'guests_confirmed' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RSVP_INVITATION_NOT_FOUND');
    }

    public function test_rsvp_returns_422_when_seats_exceeded(): void
    {
        $event = Event::factory()->create();
        Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => 'CAPCODE01',
            'invited_seats'   => 2,
            'rsvp_status'     => 'pending',
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/rsvp", [
            'invitation_code'  => 'CAPCODE01',
            'rsvp_status'      => 'yes',
            'guests_confirmed' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RSVP_SEATS_EXCEEDED');
    }

    public function test_rsvp_status_no_sets_guests_confirmed_to_zero(): void
    {
        $event = Event::factory()->create();
        $guest = Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => 'NOCODE001',
            'invited_seats'   => 2,
            'rsvp_status'     => 'yes',
            'guests_confirmed' => 2,
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/rsvp", [
            'invitation_code' => 'NOCODE001',
            'rsvp_status'     => 'no',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.rsvp_status', 'no')
            ->assertJsonPath('data.guests_confirmed', 0);

        $this->assertDatabaseHas('guests', [
            'id'               => $guest->id,
            'rsvp_status'      => 'no',
            'guests_confirmed' => 0,
        ]);
    }

    public function test_rsvp_accepts_dietary_tags(): void
    {
        $event = Event::factory()->create();
        Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => 'DIETCODE1',
            'invited_seats'   => 2,
            'rsvp_status'     => 'pending',
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/rsvp", [
            'invitation_code'  => 'DIETCODE1',
            'rsvp_status'      => 'yes',
            'guests_confirmed' => 1,
            'dietary_tags'     => ['vegano', 'sin_gluten'],
            'dietary_notes'    => 'Alergia al trigo',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.dietary_notes', 'Alergia al trigo');

        $this->assertNotNull(
            Guest::where('invitation_code', 'DIETCODE1')->first()->dietary_tags
        );
    }

    public function test_rsvp_rejects_invalid_dietary_tag(): void
    {
        $event = Event::factory()->create();
        Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => 'BADTAG001',
            'rsvp_status'     => 'pending',
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/rsvp", [
            'invitation_code'  => 'BADTAG001',
            'rsvp_status'      => 'yes',
            'guests_confirmed' => 1,
            'dietary_tags'     => ['invalid_tag'],
        ]);

        $response->assertStatus(422);
    }

    public function test_rsvp_requires_invitation_code_and_status(): void
    {
        $event = Event::factory()->create();

        $this->postJson("/api/v1/events/{$event->slug}/rsvp", [])
            ->assertStatus(422);
    }

    public function test_rsvp_submit_returns_404_for_unknown_event(): void
    {
        $this->postJson('/api/v1/events/nonexistent-event/rsvp', [
            'invitation_code' => 'ANY',
            'rsvp_status'     => 'no',
        ])->assertStatus(404);
    }
}
