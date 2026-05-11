<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSchedulesTest extends TestCase
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

    public function test_master_can_create_schedule_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/schedules", [
                'title' => 'Ceremonia',
                'description' => 'Ceremonia religiosa',
                'starts_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(10)->addHour()->format('Y-m-d H:i:s'),
                'location_label' => 'Parroquia San Miguel',
                'location_type' => 'ceremony',
                'display_order' => 0,
                'is_enabled' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Ceremonia')
            ->assertJsonPath('data.location_type', 'ceremony');

        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'title' => 'Ceremonia',
        ]);
    }

    public function test_master_can_list_own_event_schedules(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);

        EventSchedule::factory()->count(2)->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/schedules");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_master_cannot_access_other_owner_event_schedules(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1 = $this->loginToken($master1);
        $event2 = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event2->slug}/schedules");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_master_can_update_schedule_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);
        $schedule = EventSchedule::factory()->create([
            'event_id' => $event->id,
            'title' => 'Actividad inicial',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}/schedules/{$schedule->public_id}", [
                'title' => 'Actividad actualizada',
                'display_order' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Actividad actualizada')
            ->assertJsonPath('data.display_order', 3);
    }

    public function test_master_can_delete_schedule_for_own_event(): void
    {
        $master = $this->makeMaster();
        $token = $this->loginToken($master);
        $event = Event::factory()->create(['owner_id' => $master->id]);
        $schedule = EventSchedule::factory()->create(['event_id' => $event->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/schedules/{$schedule->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('event_schedules', ['id' => $schedule->id]);
    }

    public function test_unauthenticated_cannot_create_schedule(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/v1/events/{$event->slug}/schedules", [
            'title' => 'Sin auth',
            'starts_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(401);
    }
}
