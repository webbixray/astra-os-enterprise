<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | L5-Swagger Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file drives the swagger-php / l5-swagger documentation
    | generation for the Astra OS Enterprise API. All #[OA] attributes in the
    | scan paths will be picked up automatically.
    |
    | Generate documentation: php artisan l5-swagger:generate
    | View documentation:     /api/documentation
    |
    */

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | API Info
        |--------------------------------------------------------------------------
        */
        'title' => 'Astra OS Enterprise API',
        'version' => env('APP_VERSION', '1.2.0'),
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
                'description' => 'API v1 Server',
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
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Directories where swagger-php will look for #[OA] attributes.
    |
    */
    'paths' => [
        'scan' => [
            base_path('app/Http/Controllers/Api'),
            base_path('app/Http/Requests'),
            base_path('app/Http/Resources'),
            base_path('app/Domain'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Output & UI Configuration
        |--------------------------------------------------------------------------
        */
        'output' => storage_path('api-docs'),

        'swagger_ui' => 'api/documentation',

        /*
        |--------------------------------------------------------------------------
        | Annotations Base Path
        |--------------------------------------------------------------------------
        */
        'annotations' => [
            'base_path' => base_path('app'),
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
    | Default Responses
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
        '*/debugbar/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | L5-Swagger Specific
    |--------------------------------------------------------------------------
    */
    'l5-swagger' => [
        'default' => true,
        'documentations' => [
            'default' => [
                'api' => [
                    'title' => 'Astra OS Enterprise API v1',
                ],
                'routes' => [
                    'api' => 'api/documentation',
                ],
                'paths' => [
                    'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', false),
                ],
            ],
        ],
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
        'proxy' => false,
        'additional_configs' => [
            'operations_sort' => null,
            'validator_url' => null,
        ],
    ],
];
