<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventRomanticPhrase;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RomanticPhrasesTest extends TestCase
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

    // --- index ---

    public function test_master_can_list_own_event_romantic_phrases(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventRomanticPhrase::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/romantic-phrases");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_master_cannot_list_romantic_phrases_of_other_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/romantic-phrases");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    // --- store ---

    public function test_master_can_create_romantic_phrase_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/romantic-phrases", [
                'phrase'        => 'Eres mi hoy y todo mi mañana.',
                'author'        => 'Anónimo',
                'display_order' => 1,
                'is_enabled'    => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.phrase', 'Eres mi hoy y todo mi mañana.')
            ->assertJsonPath('data.author', 'Anónimo');

        $this->assertDatabaseHas('event_romantic_phrases', [
            'event_id' => $event->id,
            'phrase'   => 'Eres mi hoy y todo mi mañana.',
        ]);
    }

    public function test_store_requires_phrase(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/romantic-phrases", [
                'author' => 'Neruda',
            ]);

        $response->assertStatus(422);
    }

    public function test_store_rejects_phrase_exceeding_max_length(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/romantic-phrases", [
                'phrase' => str_repeat('a', 501),
            ]);

        $response->assertStatus(422);
    }

    public function test_master_cannot_create_phrase_for_other_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->postJson("/api/v1/events/{$event2->slug}/romantic-phrases", [
                'phrase' => 'Frase no autorizada.',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_create_romantic_phrase(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/v1/events/{$event->slug}/romantic-phrases", [
            'phrase' => 'Sin auth',
        ]);

        $response->assertStatus(401);
    }

    // --- update ---

    public function test_master_can_update_romantic_phrase_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $phrase = EventRomanticPhrase::factory()->create([
            'event_id' => $event->id,
            'phrase'   => 'Frase original.',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/romantic-phrases/{$phrase->public_id}", [
                'phrase'        => 'Frase actualizada.',
                'display_order' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.phrase', 'Frase actualizada.')
            ->assertJsonPath('data.display_order', 5);
    }

    public function test_master_cannot_update_phrase_of_other_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $phrase  = EventRomanticPhrase::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->putJson("/api/v1/events/{$event2->slug}/romantic-phrases/{$phrase->public_id}", [
                'phrase' => 'Intento no autorizado.',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    // --- destroy ---

    public function test_master_can_delete_romantic_phrase_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $phrase = EventRomanticPhrase::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/romantic-phrases/{$phrase->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('event_romantic_phrases', ['id' => $phrase->id]);
    }

    public function test_master_cannot_delete_phrase_of_other_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $phrase  = EventRomanticPhrase::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->deleteJson("/api/v1/events/{$event2->slug}/romantic-phrases/{$phrase->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_delete_returns_404_for_wrong_public_id(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/romantic-phrases/nonexistent-id");

        $response->assertStatus(404);
    }

    // --- resource shape ---

    public function test_response_exposes_public_id_not_internal_id(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/romantic-phrases", [
                'phrase' => 'Test frase pública.',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'phrase', 'author', 'display_order', 'is_enabled', 'created_at', 'updated_at']])
            ->assertJsonMissingPath('data.event_id');
    }
}
