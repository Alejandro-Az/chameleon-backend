<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\Ops\ReadinessChecker;
use Exception;

class KaanInstallCommand extends Command
{
    protected $signature = 'kaan:install';
    protected $description = 'Bootstrap Kaan Core: migrate, seed, create admin';

    public function handle(ReadinessChecker $checker): int
    {
        $this->info('');
        $this->info('🔧 Kaan Core Install');
        $this->info('   ' . config('kaan.name') . ' v' . config('kaan.version'));
        $this->info('');

        // 0. Production Readiness Checks
        $env = config('app.env');
        if (in_array($env, ['production', 'staging'], true)) {
            $this->info("🔍 Running Readiness Checks for [{$env}] environment...");
            $results = $checker->runAllChecks();
            $fails = collect($results)->where('status', 'FAIL');

            if ($fails->isNotEmpty()) {
                $this->error('❌ Installation ABORTED: Production readiness checks failed.');
                $this->line('');
                foreach ($fails as $fail) {
                    $this->error("   - [{$fail['id']}] {$fail['message']}");
                    if ($fail['hint']) {
                        $this->line("     <fg=gray>Hint: {$fail['hint']}</>");
                    }
                }
                $this->line('');
                $this->warn('Fix these issues before running kaan:install in this environment.');
                return self::FAILURE;
            }
            $this->info('✅ Readiness Checks passed.');
            $this->info('');
        }

        // 1. Clear caches
        $this->call('optimize:clear');
        
        // 2. Migrate
        $this->info('');
        $this->info('📦 Running migrations...');
        $this->call('migrate', ['--force' => true]);

        // 3. Seed RBAC (core capability — always active)
        $this->info('');
        $this->info('🔐 Seeding roles & permissions...');
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder',
            '--force' => true,
        ]);

        // 4. Create admin user
        $this->info('');
        $this->createAdminUser();

        // 5. Summary
        $this->info('');
        $this->info('✅ Kaan Core installed successfully!');
        $this->info('');
        $this->table(
            ['Feature', 'Status'],
            collect(config('kaan.features', []))->map(function ($enabled, $name) {
                return [$name, $enabled ? '✅ ON' : '❌ OFF'];
            })->prepend(['auth_sessions', '🔒 CORE'])->prepend(['rbac', '🔒 CORE'])->toArray()
        );
        $this->info('');

        return self::SUCCESS;
    }

    private function createAdminUser(): void
    {
        $email = config('kaan.admin.bootstrap_email', 'admin@kaan.dev');
        $password = config('kaan.admin.bootstrap_password');

        if (empty($password)) {
            $this->error('❌ KAAN_ADMIN_PASSWORD no está definido en .env.');
            $this->warn('   No se puede crear el admin con una contraseña vacía o predecible.');
            $this->warn('   Define KAAN_ADMIN_PASSWORD en tu .env y vuelve a ejecutar kaan:install.');
            // Abortar solo la creación del admin, no toda la instalación.
            // Las migraciones y seeds ya corrieron — el admin puede crearse manualmente después.
            return;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $this->info("👤 Admin ya existe: {$email}");
        } else {
            $user = User::create([
                'name'      => 'Admin',
                'email'     => $email,
                'username'  => 'admin',
                'password'  => $password,
                'public_id' => (string) Str::ulid(),
                'status'    => 'active',
            ]);

            $user->assignRole('admin');

            $this->info("👤 Admin creado: {$email}");
        }
    }
}
