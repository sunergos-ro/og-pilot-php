<?php

declare(strict_types=1);

namespace Sunergos\OgPilot;

class OgPilot
{
    private static ?Configuration $config = null;

    /**
     * Get the current configuration.
     */
    public static function config(): Configuration
    {
        if (self::$config === null) {
            self::$config = new Configuration();
        }

        return self::$config;
    }

    /**
     * Set the current request context for automatic path resolution.
     *
     * @param array $request Request info with 'url' and/or 'path' keys
     *
     * @example
     * ```php
     * OgPilot::setCurrentRequest(['url' => $_SERVER['REQUEST_URI']]);
     * ```
     */
    public static function setCurrentRequest(array $request): void
    {
        RequestContext::setCurrentRequest($request);
    }

    /**
     * Clear the current request context.
     */
    public static function clearCurrentRequest(): void
    {
        RequestContext::clearCurrentRequest();
    }

    /**
     * Run a callback with the given request context.
     *
     * @template T
     * @param array $request Request info with 'url' and/or 'path' keys
     * @param callable(): T $callback
     * @return T
     */
    public static function withRequestContext(array $request, callable $callback): mixed
    {
        return RequestContext::withRequestContext($request, $callback);
    }

    /**
     * Configure OG Pilot with a callback.
     *
     * @param callable(Configuration): void $callback
     */
    public static function configure(callable $callback): Configuration
    {
        $callback(self::config());
        return self::config();
    }

    /**
     * Set configuration from an array of options.
     */
    public static function setConfig(array $options): Configuration
    {
        self::$config = new Configuration($options);
        return self::$config;
    }

    /**
     * Reset configuration to defaults.
     */
    public static function resetConfig(): void
    {
        self::$config = null;
    }

    /**
     * Get a new client instance using the current configuration.
     */
    public static function client(): Client
    {
        return new Client(self::config());
    }

    /**
     * Create a new client with custom options.
     */
    public static function createClient(array $options = []): Client
    {
        return new Client($options);
    }

    /**
     * Create an OG Pilot image using the default configuration.
     *
     * @param array $params Image parameters (template, title, etc.)
     * @param array $options Request options (json, iat, headers)
     * @return string|array Returns URL string or JSON array based on options
     */
    public static function createImage(array $params = [], array $options = []): string|array
    {
        return self::client()->createImage($params, $options);
    }
}
