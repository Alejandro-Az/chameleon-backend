<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kaan Core — Project Metadata
    |--------------------------------------------------------------------------
    */
    'name'    => env('KAAN_NAME', 'Kaan Core Backend'),
    'version' => env('KAAN_VERSION', '0.2.0-alpha'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    | Global pagination defaults. These can be overridden at runtime by
    | Policy Center if features.policy_center is enabled.
    */
    'pagination' => [
        'default_per_page' => env('KAAN_PAGINATION_DEFAULT_PER_PAGE', 15),
        'max_per_page'     => env('KAAN_PAGINATION_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | Toggle modules on/off. When a feature is OFF:
    |   - Routes are NOT registered
    |   - Seeders SKIP related data
    |   - Services become NO-OP
    |
    | NOTE: auth_sessions and rbac are core capabilities and NOT optional.
    |       They are always active and do not appear here.
    */
    'features' => [
        'admin'           => env('KAAN_FEATURE_ADMIN', true),
        'audit'           => env('KAAN_FEATURE_AUDIT', true),
        'login_attempts'  => env('KAAN_FEATURE_LOGIN_ATTEMPTS', true),
        'admin_security'  => env('KAAN_FEATURE_ADMIN_SECURITY', true),
        'api_keys'        => env('KAAN_FEATURE_API_KEYS', false),
        'policy_center'   => env('KAAN_FEATURE_POLICY_CENTER', false),
        'appointments'    => env('KAAN_FEATURE_APPOINTMENTS', false),
        'events'          => env('KAAN_FEATURE_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Bootstrap
    |--------------------------------------------------------------------------
    | Credentials for the initial admin user created by kaan:install.
    */
    'admin' => [
        'bootstrap_email'    => env('KAAN_ADMIN_EMAIL', 'admin@kaan.dev'),
        'bootstrap_password' => env('KAAN_ADMIN_PASSWORD'), // Sin default — debe estar en .env
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    | Set retention_days to null/0 to disable pruning.
    */
    'audit' => [
        'retention_days' => env('KAAN_AUDIT_RETENTION_DAYS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Links
    |--------------------------------------------------------------------------
    | Used for headless emails, reset password, etc.
    */
    'frontend' => [
        'reset_password_url' => env('KAAN_FRONTEND_RESET_PASSWORD_URL'),
        'verify_email_url'   => env('KAAN_FRONTEND_VERIFY_EMAIL_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth Behaviors
    |--------------------------------------------------------------------------
    | Behaviours for the auth system like email verification requirement.
    */
    'auth' => [
        'login_field'                 => env('KAAN_AUTH_LOGIN_FIELD', 'email'),
        'require_verified_email'      => env('KAAN_AUTH_REQUIRE_VERIFIED_EMAIL', false),
        'verify_email_expire'         => env('KAAN_AUTH_VERIFY_EMAIL_EXPIRE_MINUTES', 60),
        'allow_public_registration'   => env('KAAN_AUTH_ALLOW_PUBLIC_REGISTRATION', false),
        'register_issue_token'        => env('KAAN_AUTH_REGISTER_ISSUE_TOKEN', true),
        'registration_default_status' => env('KAAN_AUTH_REGISTRATION_DEFAULT_STATUS', 'active'),
        'default_role'                => env('KAAN_AUTH_DEFAULT_ROLE', 'user'),
        'register_require_name'       => env('KAAN_AUTH_REGISTER_REQUIRE_NAME', true),
    ],


    /*
    |--------------------------------------------------------------------------
    | Security Center
    |--------------------------------------------------------------------------
    | Retention periods and admin exposure settings.
    */
    'security' => [
        'sessions' => [
            'retention' => [
                'expired_days' => env('KAAN_SECURITY_SESSIONS_EXPIRED_DAYS', 7),
                'revoked_days' => env('KAAN_SECURITY_SESSIONS_REVOKED_DAYS', 30),
            ],
        ],
        'login_attempts' => [
            'retention_days'                 => env('KAAN_SECURITY_LOGIN_ATTEMPTS_RETENTION_DAYS', 30),
            'expose_raw_identifier_to_admin' => env('KAAN_SECURITY_EXPOSE_RAW_LOGIN', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Operations / Deployment Configuration
    |--------------------------------------------------------------------------
    | Critical settings for checking application health.
    */
    'ops' => [
        'scheduler_heartbeat_max_age_minutes' => env('KAAN_SCHEDULER_HEARTBEAT_MAX_AGE_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys / Service Accounts
    |--------------------------------------------------------------------------
    */
    'api_keys' => [
        'prefix'        => env('KAAN_API_KEYS_PREFIX', 'kk_live_'),
        'prefix_length' => env('KAAN_API_KEYS_PREFIX_LENGTH', 12),
        'exchange' => [
            'attempts'       => env('KAAN_API_KEYS_EXCHANGE_ATTEMPTS', 10),
            'decay_seconds'  => env('KAAN_API_KEYS_EXCHANGE_DECAY_SECONDS', 60),
            'verbose_errors' => env('KAAN_API_KEYS_EXCHANGE_VERBOSE_ERRORS', false),
        ],
        'retention_days' => env('KAAN_API_KEYS_RETENTION_DAYS', 90),
    ],
];
