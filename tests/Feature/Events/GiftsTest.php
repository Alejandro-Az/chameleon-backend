<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Enums\ModuleKey;
use App\Models\Event;
use App\Models\EventModuleConfig;
use App\Models\Gift;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftsTest extends TestCase
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
    // INDEX
    // -------------------------------------------------------------------------

    public function test_master_can_list_gifts_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        Gift::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/gifts");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_master_cannot_list_gifts_for_another_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/gifts");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_list_gifts(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/v1/events/{$event->slug}/gifts")
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    public function test_master_can_create_gift_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gifts", [
                'name'          => 'Vajilla de porcelana',
                'description'   => 'Juego completo para 8 personas',
                'store_label'   => 'Liverpool',
                'url'           => 'https://liverpool.com.mx/vajilla',
                'quantity'      => 1,
                'display_order' => 0,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Vajilla de porcelana')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.quantity', 1)
            ->assertJsonPath('data.quantity_reserved', 0);

        $this->assertDatabaseHas('gifts', [
            'event_id' => $event->id,
            'name'     => 'Vajilla de porcelana',
        ]);
    }

    public function test_store_gift_fails_validation_without_required_fields(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gifts", []);

        $response->assertStatus(422);
    }

    public function test_master_cannot_create_gift_for_another_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->postJson("/api/v1/events/{$event2->slug}/gifts", [
                'name'     => 'Cuchillos',
                'quantity' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_create_gift(): void
    {
        $event = Event::factory()->create();

        $this->postJson("/api/v1/events/{$event->slug}/gifts", [
            'name'     => 'Cuchillos',
            'quantity' => 1,
        ])->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    public function test_master_can_update_gift_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $gift   = Gift::factory()->create(['event_id' => $event->id, 'name' => 'Original']);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/gifts/{$gift->public_id}", [
                'name'          => 'Actualizado',
                'quantity'      => 2,
                'display_order' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Actualizado')
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.display_order', 5);
    }

    public function test_master_cannot_update_gift_from_another_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $gift    = Gift::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->putJson("/api/v1/events/{$event2->slug}/gifts/{$gift->public_id}", [
                'name'     => 'Hackeo',
                'quantity' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_update_gift_returns_404_for_unknown_public_id(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/gifts/nonexistent-id", [
                'name'     => 'Ghost',
                'quantity' => 1,
            ]);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    public function test_master_can_delete_gift_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);
        $gift   = Gift::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/gifts/{$gift->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('gifts', ['id' => $gift->id]);
    }

    public function test_master_cannot_delete_gift_from_another_owner_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event2  = Event::factory()->create(['owner_id' => $master2->id]);
        $gift    = Gift::factory()->create(['event_id' => $event2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->deleteJson("/api/v1/events/{$event2->slug}/gifts/{$gift->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_delete_gift_returns_404_for_unknown_public_id(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/gifts/nonexistent-id")
            ->assertStatus(404);
    }

    public function test_unauthenticated_cannot_delete_gift(): void
    {
        $event = Event::factory()->create();
        $gift  = Gift::factory()->create(['event_id' => $event->id]);

        $this->deleteJson("/api/v1/events/{$event->slug}/gifts/{$gift->public_id}")
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // auto_approve — regresión
    // El regalo del master siempre queda en pending al crearse.
    // El ciclo de vida de Gift es pending → reserved → purchased;
    // no existe estado approved, por lo que auto_approve no aplica.
    // -------------------------------------------------------------------------

    public function test_gift_is_always_pending_on_create_regardless_of_auto_approve_flag(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventModuleConfig::factory()->create([
            'event_id'     => $event->id,
            'module_key'   => ModuleKey::Gifts->value,
            'enabled'      => true,
            'auto_approve' => true,
            'order'        => 1,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gifts", [
                'name'     => 'Mesa de madera',
                'quantity' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', Gift::STATUS_PENDING);
    }
}
