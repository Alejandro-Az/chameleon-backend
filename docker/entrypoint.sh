#!/bin/bash
set -e

INSTALL_FLAG="/var/www/backend/.installed"

# ─── Generar .env desde variables de entorno Docker ───────────────────────
echo "[Kaan] Generando .env..."
cat > /var/www/backend/.env << EOF
APP_NAME="${APP_NAME:-Kaan Core}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=stderr
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=${DB_DATABASE:-kaan_core}
DB_USERNAME=${DB_USERNAME:-kaan}
DB_PASSWORD=${DB_PASSWORD:-secret}

CACHE_DRIVER=${CACHE_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=120

JWT_SECRET=${JWT_SECRET:-}
JWT_TTL=${JWT_TTL:-60}
JWT_REFRESH_TTL=${JWT_REFRESH_TTL:-20160}

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_HOST=${MAIL_HOST:-localhost}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@kaan.dev}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-Kaan Core}"

KAAN_NAME="${KAAN_NAME:-Kaan Core}"
KAAN_VERSION="${KAAN_VERSION:-0.2.1-alpha}"
KAAN_ADMIN_EMAIL=${KAAN_ADMIN_EMAIL:-admin@kaan.dev}
KAAN_ADMIN_PASSWORD=${KAAN_ADMIN_PASSWORD:-Secret123456}
KAAN_AUTH_LOGIN_FIELD=${KAAN_AUTH_LOGIN_FIELD:-email_or_username}
KAAN_AUTH_REQUIRE_VERIFIED_EMAIL=${KAAN_AUTH_REQUIRE_VERIFIED_EMAIL:-false}
KAAN_AUTH_ALLOW_PUBLIC_REGISTRATION=${KAAN_AUTH_ALLOW_PUBLIC_REGISTRATION:-false}
KAAN_AUTH_REGISTER_ISSUE_TOKEN=${KAAN_AUTH_REGISTER_ISSUE_TOKEN:-true}
KAAN_AUTH_DEFAULT_ROLE=${KAAN_AUTH_DEFAULT_ROLE:-user}
KAAN_FEATURE_ADMIN=${KAAN_FEATURE_ADMIN:-true}
KAAN_FEATURE_AUDIT=${KAAN_FEATURE_AUDIT:-true}
KAAN_FEATURE_ADMIN_SECURITY=${KAAN_FEATURE_ADMIN_SECURITY:-true}
KAAN_FEATURE_LOGIN_ATTEMPTS=${KAAN_FEATURE_LOGIN_ATTEMPTS:-true}
KAAN_FEATURE_API_KEYS=${KAAN_FEATURE_API_KEYS:-false}
KAAN_FEATURE_POLICY_CENTER=${KAAN_FEATURE_POLICY_CENTER:-false}
KAAN_PAGINATION_DEFAULT=${KAAN_PAGINATION_DEFAULT:-15}
KAAN_PAGINATION_MAX=${KAAN_PAGINATION_MAX:-100}
KAAN_FRONTEND_VERIFY_EMAIL_URL=${KAAN_FRONTEND_VERIFY_EMAIL_URL:-}
KAAN_FRONTEND_RESET_PASSWORD_URL=${KAAN_FRONTEND_RESET_PASSWORD_URL:-}
CORS_ALLOWED_ORIGINS=${CORS_ALLOWED_ORIGINS:-http://localhost}
EOF

chown www-data:www-data /var/www/backend/.env

# ─── Generar APP_KEY si no está definido ──────────────────────────────────
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[Kaan] Generando APP_KEY..."
    php artisan key:generate --force
fi

# ─── Primer arranque: bootstrap completo ──────────────────────────────────
if [ ! -f "$INSTALL_FLAG" ]; then
    echo "[Kaan] ============================================"
    echo "[Kaan]  Primera ejecución detectada — iniciando"
    echo "[Kaan]  bootstrap completo de Kaan Core..."
    echo "[Kaan] ============================================"

    # Esperar MySQL con reintentos
    echo "[Kaan] Esperando MySQL..."
    RETRIES=30
    until php -r "
        try {
            \$pdo = new PDO(
                'mysql:host=db;dbname=${DB_DATABASE:-kaan_core}',
                '${DB_USERNAME:-kaan}',
                '${DB_PASSWORD:-secret}'
            );
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        RETRIES=$((RETRIES - 1))
        if [ $RETRIES -le 0 ]; then
            echo "[Kaan] ERROR: MySQL no respondió tras 30 intentos. Abortando."
            exit 1
        fi
        echo "[Kaan] Esperando MySQL... ($RETRIES intentos restantes)"
        sleep 2
    done
    echo "[Kaan] MySQL listo."

    # Migraciones
    echo "[Kaan] Ejecutando migraciones..."
    php artisan migrate --force

    # Bootstrap completo de Kaan (seeds + admin)
    echo "[Kaan] Ejecutando kaan:install..."
    php artisan kaan:install --no-interaction 2>/dev/null \
        || php artisan db:seed --force

    # Generar docs Swagger
    echo "[Kaan] Generando documentación Swagger..."
    php artisan l5-swagger:generate 2>/dev/null || true

    # Cachear config/rutas para producción
    echo "[Kaan] Cacheando configuración..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    touch "$INSTALL_FLAG"
    chown www-data:www-data "$INSTALL_FLAG"

    echo "[Kaan] ✓ Bootstrap completado correctamente."
else
    echo "[Kaan] Instalación existente detectada — arrancando normalmente."
    # Asegurarse del caché
    php artisan config:cache 2>/dev/null || true
fi

# ─── Permisos finales ──────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
