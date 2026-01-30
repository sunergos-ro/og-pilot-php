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
