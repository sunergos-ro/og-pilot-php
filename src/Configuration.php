<?php

declare(strict_types=1);

namespace Sunergos\OgPilot;

class Configuration
{
    public const DEFAULT_BASE_URL = 'https://ogpilot.com';

    public ?string $apiKey;
    public ?string $domain;
    public string $baseUrl;
    public float $connectTimeout;
    public float $timeout;
    public bool $stripExtensions;
    public bool $stripQueryParameters;

    public function __construct(array $options = [])
    {
        $this->apiKey = $options['api_key'] ?? $this->getEnv('OG_PILOT_API_KEY');
        $this->domain = $options['domain'] ?? $this->getEnv('OG_PILOT_DOMAIN');
        $this->baseUrl = $options['base_url'] ?? self::DEFAULT_BASE_URL;
        $this->connectTimeout = $options['connect_timeout'] ?? 5.0;
        $this->timeout = $options['timeout'] ?? 10.0;
        $this->stripExtensions = $options['strip_extensions'] ?? true;
        $this->stripQueryParameters = $options['strip_query_parameters'] ?? false;
    }

    private function getEnv(string $key): ?string
    {
        $value = getenv($key);
        return $value !== false ? $value : null;
    }
}
