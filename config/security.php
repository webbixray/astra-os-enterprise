<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Settings (Strict)
    |--------------------------------------------------------------------------
    |
    | Restrict cross-origin requests to the application's own origin only.
    | In production, APP_URL should be set to the exact canonical domain.
    |
    */
    'cors' => [
        'allowed_origins' => [env('APP_URL')],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Version', 'X-Request-ID'],
        'exposed_headers' => ['X-API-Version', 'X-Request-ID'],
        'max_age' => 86400,
        'supports_credentials' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Strict CSP directives to mitigate XSS and data injection attacks.
    | Only self-origin resources are allowed by default.
    |
    */
    'csp' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'connect-src' => ["'self'"],
        'frame-src' => ["'none'"],
        'object-src' => ["'none'"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | HTTP response headers that enforce browser-side security policies.
    |
    */
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Defaults
    |--------------------------------------------------------------------------
    |
    | Global and authentication-specific rate limit configuration.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'global_max_attempts' => 60,
        'global_decay_minutes' => 1,
        'auth_max_attempts' => 10,
        'auth_decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Secret Rotation
    |--------------------------------------------------------------------------
    |
    | Configure how often secrets, tokens, and keys should be rotated.
    | The TokenRotationService uses these intervals to identify stale
    | credentials and issue replacements.
    |
    */
    'secret_rotation' => [
        'enabled' => true,
        'app_key_rotation_days' => 90,
        'webhook_secret_rotation_days' => 180,
        'token_rotation_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Model-field-level encryption settings. Encrypted fields are transparently
    | encrypted when stored and decrypted when read using Laravel's built-in
    | encryption (AES-256-CBC via the APP_KEY).
    |
    */
    'encryption' => [
        'model_encryption' => true,
        'encrypted_fields' => [
            'social_accounts' => ['access_token', 'refresh_token'],
            'users' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    |
    | Server-side input sanitisation rules applied by the InputSanitizer
    | middleware before the request reaches controllers.
    |
    */
    'input_validation' => [
        'strip_unknown_fields' => true,
        'max_input_length' => 10000,
        'block_sql_injection_patterns' => true,
        'block_xss_patterns' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Session cookie hardening to prevent hijacking and fixation attacks.
    |
    */
    'session' => [
        'secure' => true,
        'http_only' => true,
        'same_site' => 'strict',
        'lifetime' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    |
    | Controls for the audit logging subsystem including retention policy
    | and sensitive-field redaction.
    |
    */
    'audit' => [
        'log_all_requests' => true,
        'log_request_body' => false,
        'log_headers' => false,
        'retention_days' => 90,
        'sensitive_fields' => ['password', 'token', 'secret', 'authorization'],
    ],
];
