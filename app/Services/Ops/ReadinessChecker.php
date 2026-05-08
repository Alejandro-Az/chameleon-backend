<?php

namespace App\Services\Ops;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class ReadinessChecker
{
    private const CACHE_KEY_HEARTBEAT = 'kaan:scheduler:last_run';

    /**
     * Define the strict password policy for admin users in production.
     */
    public static function getAdminPasswordRules(): Password
    {
        return Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(3); // Optional: checks HIBP, requires internet
    }

    /**
     * Run all readness checks and return an array of results.
     * 
     * Output format per item:
     * [
     *     'id'      => 'check_name',
     *     'status'  => 'OK' | 'WARN' | 'FAIL',
     *     'message' => 'Human readable description',
     *     'hint'    => 'How to fix it',
     * ]
     */
    public function runAllChecks(): array
    {
        $env = config('app.env');
        $isProdOrStaging = in_array($env, ['production', 'staging'], true);

        return [
            $this->checkEnvDebug($isProdOrStaging),
            $this->checkAppKey(),
            $this->checkJwtSecret(),
            $this->checkAdminEmail(),
            $this->checkAdminPassword($isProdOrStaging),
            $this->checkQueueConnection($isProdOrStaging),
            $this->checkMailConfig($isProdOrStaging),
            $this->checkRetentionConfig($isProdOrStaging),
            $this->checkSchedulerHeartbeat($isProdOrStaging),
        ];
    }

    private function checkEnvDebug(bool $isProdOrStaging): array
    {
        $debug = config('app.debug');

        if ($isProdOrStaging && $debug) {
            return [
                'id' => 'env_debug',
                'status' => 'FAIL',
                'message' => 'APP_DEBUG está activado en producción/staging.',
                'hint' => 'Cambia APP_DEBUG=false en el archivo .env',
            ];
        }

        return [
            'id' => 'env_debug',
            'status' => 'OK',
            'message' => 'APP_DEBUG está configurado correctamente.',
            'hint' => null,
        ];
    }

    private function checkAppKey(): array
    {
        $key = config('app.key');

        if (empty($key)) {
            return [
                'id' => 'app_key',
                'status' => 'FAIL',
                'message' => 'APP_KEY no está definido.',
                'hint' => 'Ejecuta: php artisan key:generate',
            ];
        }

        return [
            'id' => 'app_key',
            'status' => 'OK',
            'message' => 'APP_KEY configurado.',
            'hint' => null,
        ];
    }

    private function checkJwtSecret(): array
    {
        $secret = config('jwt.secret');

        if (empty($secret)) {
            return [
                'id' => 'jwt_secret',
                'status' => 'FAIL',
                'message' => 'JWT_SECRET no está definido.',
                'hint' => 'Ejecuta: php artisan jwt:secret',
            ];
        }

        return [
            'id' => 'jwt_secret',
            'status' => 'OK',
            'message' => 'JWT_SECRET configurado.',
            'hint' => null,
        ];
    }

    private function checkAdminEmail(): array
    {
        $email = config('kaan.admin.bootstrap_email');

        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);

        if ($validator->fails()) {
            return [
                'id' => 'admin_email',
                'status' => 'FAIL',
                'message' => 'KAAN_ADMIN_EMAIL es inválido o está vacío.',
                'hint' => 'Define un KAAN_ADMIN_EMAIL válido en el archivo .env',
            ];
        }

        return [
            'id' => 'admin_email',
            'status' => 'OK',
            'message' => 'KAAN_ADMIN_EMAIL configurado y válido.',
            'hint' => null,
        ];
    }

    private function checkAdminPassword(bool $isProdOrStaging): array
    {
        $password = config('kaan.admin.bootstrap_password');
        
        if (empty($password)) {
            return [
                'id' => 'admin_password',
                'status' => 'FAIL',
                'message' => 'KAAN_ADMIN_PASSWORD no está definido.',
                'hint' => 'Define KAAN_ADMIN_PASSWORD en el archivo .env',
            ];
        }

        // Basic denylist check
        $denylist = ['secret123456', 'password', 'admin123', 'qwerty', '123456789'];
        if (in_array(strtolower($password), $denylist, true)) {
            return [
                'id' => 'admin_password',
                'status' => 'FAIL',
                'message' => 'KAAN_ADMIN_PASSWORD usa un valor prohibido por la denylist.',
                'hint' => 'Usa una contraseña fuerte generada aleatoriamente.',
            ];
        }

        if ($isProdOrStaging) {
            $validator = Validator::make(['password' => $password], [
                'password' => ['required', self::getAdminPasswordRules()]
            ]);

            if ($validator->fails()) {
                return [
                    'id' => 'admin_password',
                    'status' => 'FAIL',
                    'message' => 'KAAN_ADMIN_PASSWORD no cumple la política de seguridad estricta.',
                    'hint' => 'Mínimo 12 caracteres, mayúsculas, minúsculas, números y símbolos.',
                ];
            }
        } elseif (strcasecmp($password, 'Secret123456') === 0) {
             return [
                'id' => 'admin_password',
                'status' => 'WARN',
                'message' => 'KAAN_ADMIN_PASSWORD usa el default (Secret123456) en desarrollo.',
                'hint' => 'Aceptable en local, pero bloqueado en producción.',
            ];
        }

        return [
            'id' => 'admin_password',
            'status' => 'OK',
            'message' => 'KAAN_ADMIN_PASSWORD configurado y robusto.',
            'hint' => null,
        ];
    }

    private function checkQueueConnection(bool $isProdOrStaging): array
    {
        $queue = config('queue.default');

        if ($isProdOrStaging && $queue === 'sync') {
            return [
                'id' => 'queue_connection',
                'status' => 'WARN',
                'message' => 'Usando driver de colas "sync" en producción/staging.',
                'hint' => 'Configura QUEUE_CONNECTION (ej. redis, database, sqs) y un worker real para no bloquear las requests HTTP.',
            ];
        }

        return [
            'id' => 'queue_connection',
            'status' => 'OK',
            'message' => 'Driver de colas configurado ("' . $queue . '").',
            'hint' => null,
        ];
    }

    private function checkMailConfig(bool $isProdOrStaging): array
    {
        $mailer = config('mail.default');

        if ($isProdOrStaging && in_array($mailer, ['log', 'array'])) {
            return [
                'id' => 'mail_mailer',
                'status' => 'WARN',
                'message' => 'Usando mailer "' . $mailer . '" en producción/staging.',
                'hint' => 'Los correos no se enviarán realmente. Configura un driver real (smtp, ses, mailgun) en MAIL_MAILER.',
            ];
        }

        return [
            'id' => 'mail_mailer',
            'status' => 'OK',
            'message' => 'Mailer configurado ("' . $mailer . '").',
            'hint' => null,
        ];
    }

    private function checkRetentionConfig(bool $isProdOrStaging): array
    {
        if (!$isProdOrStaging) {
            return [
                'id' => 'retention_config',
                'status' => 'OK',
                'message' => 'Retención de logs ignorada en entorno de desarrollo.',
                'hint' => null,
            ];
        }

        $auditRetention = config('kaan.audit.retention_days');
        
        if (empty($auditRetention)) {
            return [
                'id' => 'retention_config',
                'status' => 'WARN',
                'message' => 'KAAN_AUDIT_RETENTION_DAYS no está configurado (retención infinita).',
                'hint' => 'Configura un límite de días en .env para evitar que la base de datos crezca sin control.',
            ];
        }

        return [
            'id' => 'retention_config',
            'status' => 'OK',
            'message' => 'Políticas de retención (pruning) configuradas.',
            'hint' => null,
        ];
    }

    private function checkSchedulerHeartbeat(bool $isProdOrStaging): array
    {
        $cacheDriver = config('cache.default');
        if ($isProdOrStaging && $cacheDriver === 'array') {
            return [
                'id' => 'scheduler_heartbeat',
                'status' => 'FAIL',
                'message' => 'El driver de caché es "array". El latido (heartbeat) no persistirá entre ejecuciones.',
                'hint' => 'Configura CACHE_STORE=redis, memcached o database en .env.',
            ];
        }

        $lastRun = Cache::get(self::CACHE_KEY_HEARTBEAT);
        $maxAge = (int) config('kaan.ops.scheduler_heartbeat_max_age_minutes', 5);

        if (!$lastRun) {
            return [
                'id' => 'scheduler_heartbeat',
                'status' => $isProdOrStaging ? 'FAIL' : 'WARN',
                'message' => 'No se detecta el heartbeat del Laravel Scheduler (nunca ha corrido).',
                'hint' => 'Configura el cronjob del sistema: * * * * * cd /ruta && /usr/bin/php artisan schedule:run >> /dev/null 2>&1',
            ];
        }

        $ageMinutes = now()->diffInMinutes($lastRun);

        if ($ageMinutes > $maxAge) {
            return [
                'id' => 'scheduler_heartbeat',
                'status' => $isProdOrStaging ? 'FAIL' : 'WARN',
                'message' => "El Scheduler está detenido. Último latido hace {$ageMinutes} minutos (máximo permitido: {$maxAge} min).",
                'hint' => 'Verifica el estado de crond o el servicio que ejecuta schedule:run.',
            ];
        }

        return [
            'id' => 'scheduler_heartbeat',
            'status' => 'OK',
            'message' => "Scheduler corriendo (último latido hace {$ageMinutes} min).",
            'hint' => null,
        ];
    }
}
