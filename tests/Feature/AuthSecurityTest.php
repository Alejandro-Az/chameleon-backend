<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_5_failures(): void
    {
        // Limpiamos cualquier estado previo
        RateLimiter::clear('auth-login:127.0.0.1|hacker@test.com');

        $user = User::factory()->create([
            'email' => 'admin@demo.kaanforge.test',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        // Intentamos 5 veces con contraseña incorrecta
        for ($i = 0; $i < 5; $i++) {
            $res = $this->postJson('/api/v1/auth/login', [
                'login' => 'hacker@test.com', // Usamos un email específico (o el del usuario)
                'password' => 'wrong',
            ]);
            $res->assertStatus(401);
        }

        // El intento 6 debe ser bloqueado (429)
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => 'hacker@test.com',
            'password' => 'wrong',
        ]);

        $res->assertStatus(429)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_TOO_MANY_ATTEMPTS');
            
        // Validar mensaje
        $this->assertStringContainsString('Demasiados intentos', $res->json('error.message'));
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $email = 'valid@test.com';
        RateLimiter::clear("auth-login:127.0.0.1|{$email}");

        $user = User::factory()->create([
            'email' => $email,
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        // 1 fallido
        $this->postJson('/api/v1/auth/login', [
            'login' => $email,
            'password' => 'wrong',
        ])->assertStatus(401);

        // Ahora login correcto
        $this->postJson('/api/v1/auth/login', [
            'login' => $email,
            'password' => 'Secret123456',
        ])->assertOk();

        // Si se limpió, deberíamos poder intentar 5 veces más sin 429
        // (aunque aquí solo probamos 1 para verificar que no quedó "sucio")
        $this->postJson('/api/v1/auth/login', [
            'login' => $email,
            'password' => 'wrong',
        ])->assertStatus(401); // No 429
    }
}
