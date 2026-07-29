<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAPI / Swagger Configuration
    |--------------------------------------------------------------------------
    |
    | This config drives swagger-php / l5-swagger documentation generation.
    | Run: php artisan l5-swagger:generate
    |
    */

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | API Title & Version
        |--------------------------------------------------------------------------
        */
        'title' => 'Astra OS Enterprise API',
        'version' => env('ASTRA_OS_VERSION', '1.2.0'),
        'description' => 'Enterprise-grade API for the Astra OS AI-Native Marketing & Business Growth platform. '
            . 'Provides campaign management, hierarchical AI agents, workflow automation, social intelligence, '
            . 'and multi-platform advertising orchestration.',

        /*
        |--------------------------------------------------------------------------
        | Server Configuration
        |--------------------------------------------------------------------------
        */
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost') . '/api/v1',
                'description' => 'API v1 Base URL',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Contact & License
        |--------------------------------------------------------------------------
        */
        'contact' => [
            'name' => 'Astra OS Development Team',
            'email' => 'dev@astra-os.com',
            'url' => 'https://astra-os.com',
        ],

        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Scan Paths (where to find #[OA] attributes)
        |--------------------------------------------------------------------------
        */
        'scan' => [
            base_path('app/Http/Controllers/Api'),
            base_path('app/Http/Requests'),
            base_path('app/Http/Resources'),
            base_path('app/Domain'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Output Directory
        |--------------------------------------------------------------------------
        */
        'output' => storage_path('api-docs'),

        /*
        |--------------------------------------------------------------------------
        | Swagger UI Path
        |--------------------------------------------------------------------------
        */
        'swagger_ui' => 'api/documentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    */
    'security' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'Sanctum',
            'description' => 'Enter your Sanctum API token in the format: Bearer <token>',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'X-API-Version' => [
            'description' => 'API version identifier',
            'schema' => ['type' => 'string', 'example' => 'v1'],
        ],
        'X-Request-ID' => [
            'description' => 'Unique request identifier for tracing',
            'schema' => ['type' => 'string', 'format' => 'uuid'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Schemas
    |--------------------------------------------------------------------------
    */
    'responses' => [
        'SuccessResponse' => [
            'description' => 'Successful operation',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string', 'example' => 'Operation successful'],
                            'data' => ['type' => 'object', 'nullable' => true],
                        ],
                    ],
                ],
            ],
        ],
        'ErrorResponse' => [
            'description' => 'Error response',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string', 'example' => 'An error occurred'],
                            'errors' => ['type' => 'object', 'nullable' => true],
                        ],
                    ],
                ],
            ],
        ],
        'ValidationErrorResponse' => [
            'description' => 'Validation error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Validation failed'],
                            'errors' => [
                                'type' => 'object',
                                'example' => ['field' => ['The field is required.']],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    */
    'excludes' => [
        '*/_ignition/*',
        '*/telescope/*',
        '*/horizon/*',
    ],
];
