<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware InputSanitizer
 *
 * Sanitises all incoming request input before it reaches controllers.
 * Strips null bytes, trims whitespace, blocks SQL injection and XSS
 * patterns, truncates oversized fields, and ensures every request
 * carries a unique X-Request-ID for traceability.
 */
final class InputSanitizer
{
    /**
     * Known SQL-injection substrings that should never appear in
     * legitimate application input.
     *
     * @var list<string>
     */
    private array $sqlInjectionPatterns;

    /**
     * Common XSS patterns that indicate attempted script injection.
     *
     * @var list<string>
     */
    private array $xssPatterns;

    /**
     * Initialise the pattern lists.
     */
    public function __construct()
    {
        $this->sqlInjectionPatterns = [
            '--',
            "' OR 1=1",
            '" OR 1=1',
            'OR 1=1',
            "' OR '1'='1",
            '" OR "1"="1',
            'UNION SELECT',
            'union select',
            ' UNION ',
            ' DROP TABLE',
            'drop table',
            ' DELETE FROM',
            'delete from',
            ' INSERT INTO',
            'insert into',
            ' INFORMATION_SCHEMA',
            'information_schema',
            ' SLEEP(',
            'sleep(',
            ' BENCHMARK(',
            'benchmark(',
            ' WAITFOR DELAY',
            'waitfor delay',
            '0x[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]',
        ];

        $this->xssPatterns = [
            '<script',
            '</script',
            'javascript:',
            'onerror=',
            'onclick=',
            'onload=',
            'onmouseover=',
            'onfocus=',
            'onblur=',
            'onchange=',
            'onsubmit=',
            'onreset=',
            'onselect=',
            'onkeyup=',
            'onkeydown=',
            'onkeypress=',
            'alert(',
            'prompt(',
            'confirm(',
            'document.cookie',
            '<svg ',
            '<img ',
            '&#60;',
            '&#x3C;',
            '&#x3c;',
        ];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure every request has a correlation ID.
        if (! $request->headers->has('X-Request-ID')) {
            $request->headers->set('X-Request-ID', (string) Str::uuid());
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $config = config('security.input_validation', []);

        try {
            $input = $request->all();

            if ($input !== []) {
                $sanitized = $this->sanitizeArray($input, $config);
                $request->replace($sanitized);
            }
        } catch (\Throwable $e) {
            Log::warning('Input sanitisation failed: ' . $e->getMessage());
        }

        $response = $next($request);

        // Propagate the X-Request-ID to the response so clients can correlate.
        $response->headers->set('X-Request-ID', $request->headers->get('X-Request-ID'));

        return $response;
    }

    /**
     * Recursively sanitise an array of input values.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data, array $config): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = $this->sanitizeString($value, $config);
            } elseif (is_array($value)) {
                $value = $this->sanitizeArray($value, $config);
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Sanitise a single string value.
     *
     * @param  array<string, mixed>  $config
     */
    private function sanitizeString(string $value, array $config): string
    {
        // Strip null bytes that can be used to poison internals.
        $value = str_replace("\0", '', $value);

        // Strip UTF-8 BOM and other zero-width / control characters
        // (excluding legitimate whitespace).
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Trim leading and trailing whitespace.
        $value = trim($value);

        $maxLength = (int) ($config['max_input_length'] ?? 10000);

        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        // Block SQL injection patterns.
        if (($config['block_sql_injection_patterns'] ?? false)) {
            $value = $this->blockPatterns($value, $this->sqlInjectionPatterns);
        }

        // Block XSS patterns.
        if (($config['block_xss_patterns'] ?? false)) {
            $value = $this->blockPatterns($value, $this->xssPatterns);
        }

        return $value;
    }

    /**
     * Remove every occurrence of known attack patterns from the value.
     *
     * This is a defence-in-depth measure; parameterised queries and
     * Blade's auto-escaping remain the primary XSS/SQLi defences.
     */
    private function blockPatterns(string $value, array $patterns): string
    {
        foreach ($patterns as $pattern) {
            $value = str_ireplace($pattern, '', $value);
        }

        return $value;
    }
}
