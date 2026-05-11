<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventStory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoriesTest extends TestCase
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

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_master_can_list_stories_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventStory::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/story");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_master_cannot_list_stories_of_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/story");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_list_stories(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/v1/events/{$event->slug}/story");

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_master_can_create_story_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/story", [
                'title'         => 'Cómo nos conocimos',
                'subtitle'      => 'Una historia de amor',
                'body'          => 'Era una noche de verano cuando nuestros caminos se cruzaron por primera vez.',
                'display_order' => 0,
                'is_enabled'    => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Cómo nos conocimos');

        $this->assertDatabaseHas('event_stories', [
            'event_id' => $event->id,
            'title'    => 'Cómo nos conocimos',
        ]);
    }

    public function test_store_story_requires_body(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/story", [
                'title' => 'Sin cuerpo',
            ]);

        $response->assertStatus(422);
    }

    public function test_master_cannot_create_story_for_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->postJson("/api/v1/events/{$event2->slug}/story", [
                'body' => 'Intento no autorizado',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_create_story(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/v1/events/{$event->slug}/story", [
            'body' => 'Sin auth',
        ]);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_master_can_update_story_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $story  = EventStory::factory()->create([
            'event_id' => $event->id,
            'title'    => 'Título original',
            'body'     => 'Cuerpo original',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/story/{$story->public_id}", [
                'title'         => 'Título actualizado',
                'display_order' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Título actualizado')
            ->assertJsonPath('data.display_order', 1);
    }

    public function test_master_cannot_update_story_of_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $story   = EventStory::factory()->create([
            'event_id' => $event2->id,
            'body'     => 'Cuerpo ajeno',
        ]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->putJson("/api/v1/events/{$event2->slug}/story/{$story->public_id}", [
                'title' => 'Hackeo',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_master_can_delete_story_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $story  = EventStory::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/story/{$story->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('event_stories', ['id' => $story->id]);
    }

    public function test_master_cannot_delete_story_of_other_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $story   = EventStory::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->deleteJson("/api/v1/events/{$event2->slug}/story/{$story->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_delete_story(): void
    {
        $event = Event::factory()->create();
        $story = EventStory::factory()->create(['event_id' => $event->id]);

        $response = $this->deleteJson("/api/v1/events/{$event->slug}/story/{$story->public_id}");

        $response->assertStatus(401);
    }
}
