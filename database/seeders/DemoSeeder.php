<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Seed fijo: 2 admins demo (safe-to-rerun)
        $admins = [
            [
                'email' => 'admin@demo.kaanforge.test',
                'name' => 'Demo Admin',
                'username' => 'demo_admin',
            ],
            [
                'email' => 'admin2@demo.kaanforge.test',
                'name' => 'Demo Admin 2',
                'username' => 'demo_admin_2',
            ],
        ];

        foreach ($admins as $a) {
            /** @var User $user */
            $user = User::query()->updateOrCreate(
                ['email' => $a['email']],
                [
                    'name' => $a['name'],
                    'username' => $a['username'],
                    'password' => Hash::make('Secret123456'),
                    'status' => 'active',
                    'timezone' => 'America/Mexico_City',
                    'locale' => 'es',
                    'meta' => [
                        'company' => 'Kaan Forge Demo',
                        'bio' => 'Cuenta demo administrativa para revisión de UI/UX y flujos RBAC.',
                        'phone' => '+52 222 000 0000',
                        'seed_tag' => 'demo',
                    ],
                ]
            );

            // Evita duplicar assignments al re-correr (Spatie lo maneja bien, pero esto es claro)
            $user->syncRoles(['admin']);
        }

        // 2) Usuarios demo variados (safe-to-rerun por email prefijo)
        $statuses = ['active', 'pending', 'suspended'];

        // Creamos 20 "demo users" con emails deterministas demo+01..demo+20
        for ($i = 1; $i <= 20; $i++) {
            $n = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $email = "demo+{$n}@kaanforge.test";

            $status = $statuses[($i - 1) % count($statuses)];

            // Algunos edge cases para UI:
            $username = match ($i) {
                3 => null,                // username null
                7 => "demo_user_{$n}",
                default => "demo{$n}",
            };

            $meta = match ($i) {
                5 => null,                // meta null
                default => [
                    'company' => fake()->company(),
                    'bio' => fake()->sentence(12),
                    'phone' => fake()->e164PhoneNumber(),
                    'seed_tag' => 'demo',
                ],
            };

            /** @var User $user */
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Demo User {$n}",
                    'username' => $username,
                    'password' => Hash::make('Secret123456'),
                    'status' => $status,
                    'timezone' => 'America/Mexico_City',
                    'locale' => 'es',
                    'meta' => $meta,
                ]
            );

            // Por simplicidad, todos user (y si algún día agregas más roles, aquí puedes variar)
            $user->syncRoles(['user']);
        }
    }
}
