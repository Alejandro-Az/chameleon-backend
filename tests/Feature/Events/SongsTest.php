<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Enums\ModuleKey;
use App\Models\Event;
use App\Models\EventModuleConfig;
use App\Models\EventSong;
use App\Models\Guest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongsTest extends TestCase
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

    private function makeMaster(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('master');

        return $user;
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

    private function makeGuest(Event $event, string $code = 'CODE001'): Guest
    {
        return Guest::factory()->create([
            'event_id'        => $event->id,
            'invitation_code' => $code,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/events/{slug}/songs — public list
    // -------------------------------------------------------------------------

    public function test_public_can_list_approved_songs(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventSong::factory()->count(3)->create([
            'event_id' => $event->id,
            'status'   => EventSong::STATUS_APPROVED,
        ]);
        EventSong::factory()->create([
            'event_id' => $event->id,
            'status'   => EventSong::STATUS_REJECTED,
        ]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/songs");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_returns_empty_when_no_songs(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/songs");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_list_returns_404_for_unknown_event(): void
    {
        $response = $this->getJson('/api/v1/events/no-existe/songs');

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/events/{slug}/songs — suggest a song (public)
    // -------------------------------------------------------------------------

    public function test_guest_can_suggest_song(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $this->makeGuest($event, 'ABC123');

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'invitation_code' => 'ABC123',
            'title'           => 'Perfect',
            'artist'          => 'Ed Sheeran',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Perfect')
            ->assertJsonPath('data.artist', 'Ed Sheeran');

        $this->assertDatabaseHas('event_songs', [
            'event_id' => $event->id,
            'title'    => 'Perfect',
        ]);
    }

    public function test_suggest_requires_title(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $this->makeGuest($event, 'ABC123');

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'invitation_code' => 'ABC123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_suggest_requires_invitation_code(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'title' => 'Perfect',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_suggest_rejects_invalid_invitation_code(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'invitation_code' => 'INVALID',
            'title'           => 'Perfect',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'INVALID_INVITATION');
    }

    public function test_suggest_rejects_duplicate_song(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $guest  = $this->makeGuest($event, 'ABC123');

        EventSong::factory()->create([
            'event_id' => $event->id,
            'title'    => 'Perfect',
            'artist'   => 'Ed Sheeran',
            'status'   => EventSong::STATUS_APPROVED,
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'invitation_code' => 'ABC123',
            'title'           => 'Perfect',
            'artist'          => 'Ed Sheeran',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'DUPLICATE_SONG');
    }

    public function test_suggest_returns_404_for_unknown_event(): void
    {
        $response = $this->postJson('/api/v1/events/no-existe/songs', [
            'invitation_code' => 'ABC',
            'title'           => 'Song',
        ]);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/events/{slug}/songs/{song} — admin delete
    // -------------------------------------------------------------------------

    public function test_master_can_delete_song_from_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $song   = EventSong::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/songs/{$song->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('event_songs', ['id' => $song->id]);
    }

    public function test_master_cannot_delete_song_from_other_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $song    = EventSong::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->deleteJson("/api/v1/events/{$event2->slug}/songs/{$song->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_delete_song(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $song   = EventSong::factory()->create(['event_id' => $event->id]);

        $response = $this->deleteJson("/api/v1/events/{$event->slug}/songs/{$song->public_id}");

        $response->assertStatus(401);
    }

    public function test_delete_returns_404_for_unknown_song(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/songs/01ZZZZZZZZZZZZZZZZZZZZZZZZ");

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // auto_approve — regresión
    // -------------------------------------------------------------------------

    public function test_song_is_approved_when_module_auto_approve_is_true(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $this->makeGuest($event, 'AUTO01');

        EventModuleConfig::factory()->create([
            'event_id'     => $event->id,
            'module_key'   => ModuleKey::Songs->value,
            'enabled'      => true,
            'auto_approve' => true,
            'order'        => 1,
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'invitation_code' => 'AUTO01',
            'title'           => 'Auto Approved Song',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', EventSong::STATUS_APPROVED);
    }

    public function test_song_is_pending_when_module_auto_approve_is_false(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $this->makeGuest($event, 'PEND01');

        EventModuleConfig::factory()->create([
            'event_id'     => $event->id,
            'module_key'   => ModuleKey::Songs->value,
            'enabled'      => true,
            'auto_approve' => false,
            'order'        => 1,
        ]);

        $response = $this->postJson("/api/v1/events/{$event->slug}/songs", [
            'invitation_code' => 'PEND01',
            'title'           => 'Pending Song',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', EventSong::STATUS_PENDING);
    }
}
