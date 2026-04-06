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
    public ?string $imageType;
    public ?int $quality;
    public ?int $maxBytes;

    public function __construct(array $options = [])
    {
        $this->apiKey = $options['api_key'] ?? $this->getEnv('OG_PILOT_API_KEY');
        $this->domain = $options['domain'] ?? $this->getEnv('OG_PILOT_DOMAIN');
        $this->baseUrl = $options['base_url'] ?? self::DEFAULT_BASE_URL;
        $this->connectTimeout = $options['connect_timeout'] ?? 5.0;
        $this->timeout = $options['timeout'] ?? 10.0;
        $this->stripExtensions = $options['strip_extensions'] ?? true;
        $this->stripQueryParameters = $options['strip_query_parameters'] ?? false;
        $this->imageType = isset($options['image_type']) ? (string) $options['image_type'] : null;
        $this->quality = array_key_exists('quality', $options) ? $this->normalizeNullableInt($options['quality']) : null;
        $this->maxBytes = array_key_exists('max_bytes', $options) ? $this->normalizeNullableInt($options['max_bytes']) : null;
    }

    private function getEnv(string $key): ?string
    {
        $value = getenv($key);
        return $value !== false ? $value : null;
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
