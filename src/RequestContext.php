<?php

declare(strict_types=1);

namespace Sunergos\OgPilot;

/**
 * Request context for automatic path resolution.
 *
 * Stores the current request URL/path for use in OG Pilot image generation.
 * In Laravel, this is automatically populated via middleware.
 * In other PHP frameworks, call setCurrentRequest() in your middleware.
 */
class RequestContext
{
    private static ?array $currentRequest = null;

    /**
     * Set the current request context for automatic path resolution.
     *
     * Call this in your middleware before using OG Pilot.
     *
     * @param array $request Request info with 'url' and/or 'path' keys
     *
     * @example Vanilla PHP
     * ```php
     * RequestContext::setCurrentRequest([
     *     'url' => $_SERVER['REQUEST_URI'] ?? '/'
     * ]);
     * ```
     *
     * @example Laravel middleware
     * ```php
     * public function handle($request, $next)
     * {
     *     RequestContext::setCurrentRequest([
     *         'url' => $request->fullUrl(),
     *         'path' => $request->getRequestUri()
     *     ]);
     *     return $next($request);
     * }
     * ```
     */
    public static function setCurrentRequest(array $request): void
    {
        self::$currentRequest = $request;
    }

    /**
     * Clear the current request context.
     *
     * Call this after the request is complete if needed.
     */
    public static function clearCurrentRequest(): void
    {
        self::$currentRequest = null;
    }

    /**
     * Get the current request info.
     *
     * @internal
     */
    public static function getCurrentRequest(): ?array
    {
        return self::$currentRequest;
    }

    /**
     * Get the current request path from context or server variables.
     *
     * @internal
     */
    public static function getCurrentPath(): ?string
    {
        $request = self::$currentRequest;

        // Check explicit path first
        if (!empty($request['path'])) {
            return $request['path'];
        }

        // Extract path from URL
        if (!empty($request['url'])) {
            return self::extractPathFromUrl($request['url']);
        }

        // Fallback to server/environment variables
        return self::getPathFromServerVars();
    }

    /**
     * Run a callback with the given request context.
     *
     * The context is automatically cleared after the callback completes.
     *
     * @template T
     * @param array $request Request info with 'url' and/or 'path' keys
     * @param callable(): T $callback
     * @return T
     */
    public static function withRequestContext(array $request, callable $callback): mixed
    {
        $previous = self::$currentRequest;
        self::$currentRequest = $request;

        try {
            return $callback();
        } finally {
            self::$currentRequest = $previous;
        }
    }

    private static function extractPathFromUrl(string $url): string
    {
        // Handle full URLs
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parsed = parse_url($url);
            if ($parsed === false) {
                return $url;
            }

            $path = $parsed['path'] ?? '/';
            $query = $parsed['query'] ?? '';

            return $query !== '' ? "{$path}?{$query}" : $path;
        }

        return $url;
    }

    private static function getPathFromServerVars(): ?string
    {
        // Check various server/environment variables (similar to Ruby implementation)
        $requestUri = $_SERVER['REQUEST_URI'] ?? getenv('REQUEST_URI');
        if (!empty($requestUri) && $requestUri !== false) {
            return (string) $requestUri;
        }

        $originalFullpath = getenv('ORIGINAL_FULLPATH');
        if (!empty($originalFullpath) && $originalFullpath !== false) {
            return (string) $originalFullpath;
        }

        $pathInfo = $_SERVER['PATH_INFO'] ?? getenv('PATH_INFO');
        if (!empty($pathInfo) && $pathInfo !== false) {
            $queryString = $_SERVER['QUERY_STRING'] ?? getenv('QUERY_STRING') ?: '';
            return $queryString !== '' ? "{$pathInfo}?{$queryString}" : (string) $pathInfo;
        }

        $requestPath = getenv('REQUEST_PATH');
        if (!empty($requestPath) && $requestPath !== false) {
            return (string) $requestPath;
        }

        return null;
    }
}
