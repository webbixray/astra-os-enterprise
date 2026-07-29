<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApiVersion
{
    /**
     * Supported API versions.
     *
     * @var array<int, string>
     */
    private const array SUPPORTED_VERSIONS = ['v1'];

    /**
     * Default API version when no header is provided.
     *
     * @var string
     */
    private const string DEFAULT_VERSION = 'v1';

    /**
     * Handle an incoming request.
     *
     * Reads the X-API-Version header, validates it is a supported version,
     * attaches the version to the request attributes and response headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->header('X-API-Version', self::DEFAULT_VERSION);
        $version = strtolower(trim($version));

        // Validate version format (v followed by a number)
        if (! preg_match('/^v\d+$/i', $version)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API version format. Use format: v1, v2, etc.',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 400);
        }

        // Check if version is supported
        if (! in_array($version, self::SUPPORTED_VERSIONS, true)) {
            return response()->json([
                'success' => false,
                'message' => "API version '{$version}' is not supported. Supported versions: ".implode(', ', self::SUPPORTED_VERSIONS),
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 400);
        }

        // Attach version to request attributes for downstream use
        $request->attributes->set('api_version', $version);

        /** @var Response $response */
        $response = $next($request);

        // Add API version header to the response
        $response->headers->set('X-API-Version', $version);

        return $response;
    }

    /**
     * Get the list of supported API versions.
     *
     * @return array<int, string>
     */
    public static function supportedVersions(): array
    {
        return self::SUPPORTED_VERSIONS;
    }

    /**
     * Get the default API version.
     */
    public static function defaultVersion(): string
    {
        return self::DEFAULT_VERSION;
    }
}
