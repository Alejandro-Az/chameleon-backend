<?php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesTest extends TestCase
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

    private function makeAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'status'             => 'active',
            'password'           => 'Secret123456',
            'email_verified_at'  => now(),
        ]);
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_public_can_list_templates(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_public_can_filter_templates_by_event_type(): void
    {
        $response = $this->getJson('/api/v1/templates?event_type=wedding');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        foreach ($response->json('data') as $template) {
            $this->assertEquals('wedding', $template['event_type']);
        }
    }

    public function test_template_response_has_required_fields(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $response->assertJsonStructure([
            'ok',
            'data' => [['id', 'name', 'event_type', 'default_module_order', 'styles']],
        ]);
    }

    public function test_admin_can_create_template(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/templates', [
                'name'                 => 'Test Template',
                'event_type'           => 'birthday',
                'default_module_order' => ['rsvp', 'gifts'],
                'styles'               => [
                    'primary_color' => '#ff0000',
                    'accent_color'  => '#ffeeee',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Test Template')
            ->assertJsonPath('data.event_type', 'birthday');
    }

    public function test_non_admin_cannot_create_template(): void
    {
        $user  = User::factory()->create(['status' => 'active', 'password' => 'Secret123456', 'email_verified_at' => now()]);
        $token = $this->loginToken($user);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/templates', [
                'name'                 => 'Hack',
                'event_type'           => 'wedding',
                'default_module_order' => ['rsvp'],
                'styles'               => ['primary_color' => '#000000', 'accent_color' => '#ffffff', 'font_serif' => 'x', 'font_sans' => 'y', 'bg_image_url' => null],
            ]);

        $response->assertStatus(403);
    }

    public function test_create_template_validates_color_format(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/templates', [
                'name'                 => 'Bad Color',
                'event_type'           => 'wedding',
                'default_module_order' => ['rsvp'],
                'styles'               => [
                    'primary_color' => 'not-a-color',
                    'accent_color'  => '#ffffff',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ]);

        $response->assertStatus(422);
    }
}
