<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Enums\ModuleKey;
use App\Models\Event;
use App\Models\EventModuleConfig;
use App\Models\EventPhoto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TemplatesSeeder::class);
        Storage::fake('public');
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
    // GET /api/v1/events/{slug}/gallery
    // -------------------------------------------------------------------------

    public function test_owner_can_list_gallery_photos(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventPhoto::factory()->count(3)->create([
            'event_id' => $event->id,
            'type'     => EventPhoto::TYPE_GALLERY,
            'status'   => EventPhoto::STATUS_APPROVED,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/gallery");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_list_returns_only_approved_gallery_photos(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventPhoto::factory()->create([
            'event_id' => $event->id,
            'type'     => EventPhoto::TYPE_GALLERY,
            'status'   => EventPhoto::STATUS_APPROVED,
        ]);
        EventPhoto::factory()->create([
            'event_id' => $event->id,
            'type'     => EventPhoto::TYPE_GALLERY,
            'status'   => EventPhoto::STATUS_PENDING,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$event->slug}/gallery");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_non_owner_cannot_list_gallery(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event   = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson("/api/v1/events/{$event->slug}/gallery");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_list_gallery(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/v1/events/{$event->slug}/gallery");

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/events/{slug}/gallery
    // -------------------------------------------------------------------------

    public function test_owner_can_upload_photo(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $file = UploadedFile::fake()->image('wedding.jpg', 800, 600);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gallery", [
                'photo'   => $file,
                'caption' => 'First dance',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.caption', 'First dance')
            ->assertJsonPath('data.status', EventPhoto::STATUS_APPROVED);

        $this->assertDatabaseHas('event_photos', [
            'event_id' => $event->id,
            'caption'  => 'First dance',
            'type'     => EventPhoto::TYPE_GALLERY,
        ]);
    }

    public function test_upload_requires_photo_file(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gallery", [
                'caption' => 'No file',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_upload_rejects_non_image_file(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gallery", [
                'photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_non_owner_cannot_upload_photo(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event   = Event::factory()->create(['owner_id' => $master2->id]);

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->withHeaders($this->authHeader($token1))
            ->postJson("/api/v1/events/{$event->slug}/gallery", [
                'photo' => $file,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_unauthenticated_cannot_upload_photo(): void
    {
        $event = Event::factory()->create();
        $file  = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson("/api/v1/events/{$event->slug}/gallery", [
            'photo' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_photo_not_found_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $file   = UploadedFile::fake()->image('photo.jpg');

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events/non-existent-event/gallery', [
                'photo' => $file,
            ]);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/events/{slug}/gallery/{photo}
    // -------------------------------------------------------------------------

    public function test_owner_can_delete_photo(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $photo = EventPhoto::factory()->create([
            'event_id' => $event->id,
            'type'     => EventPhoto::TYPE_GALLERY,
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/gallery/{$photo->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('event_photos', ['id' => $photo->id]);
    }

    public function test_non_owner_cannot_delete_photo(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event   = Event::factory()->create(['owner_id' => $master2->id]);

        $photo = EventPhoto::factory()->create([
            'event_id' => $event->id,
        ]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->deleteJson("/api/v1/events/{$event->slug}/gallery/{$photo->public_id}");

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_delete_returns_404_for_unknown_photo(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}/gallery/00000000000000000000000000");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_cannot_delete_photo(): void
    {
        $event = Event::factory()->create();
        $photo = EventPhoto::factory()->create(['event_id' => $event->id]);

        $response = $this->deleteJson("/api/v1/events/{$event->slug}/gallery/{$photo->public_id}");

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // auto_approve — regresión
    // El upload del master siempre produce status=approved,
    // independientemente del flag auto_approve del módulo gallery.
    // -------------------------------------------------------------------------

    public function test_master_upload_is_always_approved_regardless_of_auto_approve_flag(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        EventModuleConfig::factory()->create([
            'event_id'     => $event->id,
            'module_key'   => ModuleKey::Gallery->value,
            'enabled'      => true,
            'auto_approve' => false,
            'order'        => 1,
        ]);

        $file = UploadedFile::fake()->image('wedding.jpg', 800, 600);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/events/{$event->slug}/gallery", [
                'photo' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', EventPhoto::STATUS_APPROVED);
    }
}
