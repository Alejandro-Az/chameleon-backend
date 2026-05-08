<?php

return [

    // =========================
    // API / Pagination Policies
    // =========================
    'api.pagination.default_per_page' => [
        'group' => 'api',
        'type' => 'integer',
        'default' => 15,
        'editable' => true,
        'sensitive' => false,
        'rules' => ['min' => 1, 'max' => 1000],
        'description' => 'Default pagination size used when per_page is not provided.',
        'config_path' => 'kaan.pagination.default_per_page',
    ],

    'api.pagination.max_per_page' => [
        'group' => 'api',
        'type' => 'integer',
        'default' => 100,
        'editable' => true,
        'sensitive' => false,
        'rules' => ['min' => 1, 'max' => 1000],
        'description' => 'Maximum per_page allowed. Controllers should clamp to this.',
        'config_path' => 'kaan.pagination.max_per_page',
    ],

    // =========================
    // Security Policies
    // =========================
    'security.api_keys.exchange.verbose_errors' => [
        'group' => 'security',
        'type' => 'boolean',
        'default' => false,
        'editable' => true,
        'sensitive' => false,
        'description' => 'If true, exchange returns specific revoked/expired codes; otherwise obfuscates as INVALID.',
        'config_path' => 'kaan.api_keys.exchange.verbose_errors',
    ],

    // =========================
    // Features catalog (read-only, ENV)
    // =========================
    'features.api_keys' => [
        'group' => 'features',
        'type' => 'boolean',
        'default' => false,
        'editable' => false,
        'sensitive' => false,
        'source' => 'env',
        'env' => 'KAAN_FEATURE_API_KEYS',
        'config_path' => 'kaan.features.api_keys',
        'description' => 'Enables API Keys module (boot-time gating).',
    ],

    'features.policy_center' => [
        'group' => 'features',
        'type' => 'boolean',
        'default' => false,
        'editable' => false,
        'sensitive' => false,
        'source' => 'env',
        'env' => 'KAAN_FEATURE_POLICY_CENTER',
        'config_path' => 'kaan.features.policy_center',
        'description' => 'Enables Policy Center module (boot-time gating).',
    ],
];
