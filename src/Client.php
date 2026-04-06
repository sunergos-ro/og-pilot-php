<?php

declare(strict_types=1);

namespace Sunergos\OgPilot;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Sunergos\OgPilot\Exceptions\ConfigurationException;
use Sunergos\OgPilot\Exceptions\RequestException;
use DateTimeInterface;

class Client
{
    private const ENDPOINT_PATH = '/api/v1/images';

    private Configuration $config;
    private ?HttpClient $httpClient;

    public function __construct(Configuration|array $config = [], ?HttpClient $httpClient = null)
    {
        $this->config = $config instanceof Configuration ? $config : new Configuration($config);
        $this->httpClient = $httpClient;
    }

    /**
     * Create an OG Pilot image.
     *
     * This method is fail-safe: if any error occurs (configuration, validation,
     * request, etc.), the error is logged and a fallback value is returned.
     *
     * @param array $params Image parameters (template, title, etc.)
     * @param array $options Request options (json, iat, headers, default)
     * @return string|array|null Returns URL string, JSON array, or fallback value on failure
     */
    public function createImage(array $params = [], array $options = []): string|array|null
    {
        $json = $options['json'] ?? false;
        $iat = $options['iat'] ?? null;
        $headers = $options['headers'] ?? [];
        $useDefault = $options['default'] ?? false;

        try {
            // Always include a path; manual overrides win, otherwise resolve from the current request.
            $resolvedParams = $this->applyConfiguredImageDefaults($params);
            $manualPath = $resolvedParams['path'] ?? null;
            unset($resolvedParams['path']);
            $resolvedParams['path'] = $this->resolvePath($manualPath, $useDefault);

            $url = $this->buildUrl($resolvedParams, $iat);
            $response = $this->request($url, $json, $headers);

            if ($json) {
                $decoded = json_decode($response['body'], true);
                if (!is_array($decoded)) {
                    throw new RequestException(
                        'OG Pilot request failed: invalid JSON response body (' . json_last_error_msg() . ')'
                    );
                }

                return $decoded;
            }

            $imageUrl = $response['final_url'] ?? $response['location'] ?? $url;
            return $this->isStatusPlaceholder($imageUrl) ? null : $imageUrl;
        } catch (\Throwable $e) {
            $this->logError(
                "OG Pilot createImage failed: {$e->getMessage()}",
                [
                    'exception' => $e::class,
                    'json' => $json,
                ]
            );

            return $json ? ['image_url' => null] : null;
        }
    }

    /**
     * Create a blog post image.
     */
    public function createBlogPostImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('blog_post', $params, $options);
    }

    /**
     * Create a podcast image.
     */
    public function createPodcastImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('podcast', $params, $options);
    }

    /**
     * Create a product image.
     */
    public function createProductImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('product', $params, $options);
    }

    /**
     * Create an event image.
     */
    public function createEventImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('event', $params, $options);
    }

    /**
     * Create a book image.
     */
    public function createBookImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('book', $params, $options);
    }

    /**
     * Create a company image.
     */
    public function createCompanyImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('company', $params, $options);
    }

    /**
     * Create a portfolio image.
     */
    public function createPortfolioImage(array $params = [], array $options = []): string|array|null
    {
        return $this->createTemplateImage('portfolio', $params, $options);
    }

    /**
     * Resolve the path parameter for the request.
     *
     * Priority: manual path > current request path > "/"
     */
    private function resolvePath(mixed $manualPath, bool $useDefault): string
    {
        // Manual path always wins if provided
        if ($manualPath !== null) {
            $pathStr = trim((string) $manualPath);
            if ($pathStr !== '') {
                return $this->normalizePath($pathStr);
            }
        }

        // If default is true, return "/"
        if ($useDefault) {
            return '/';
        }

        // Try to get path from current request context
        $currentPath = RequestContext::getCurrentPath();
        return $this->normalizePath($currentPath);
    }

    /**
     * Normalize a path to ensure it starts with "/" and handles full URLs.
     */
    private function normalizePath(?string $path): string
    {
        if ($path === null || $path === '') {
            return '/';
        }

        $cleaned = trim($path);
        if ($cleaned === '') {
            return '/';
        }

        // Extract path from full URLs
        if (str_starts_with($cleaned, 'http://') || str_starts_with($cleaned, 'https://')) {
            $parsed = parse_url($cleaned);
            if ($parsed !== false) {
                $urlPath = $parsed['path'] ?? '/';
                $query = $parsed['query'] ?? '';
                $cleaned = $query !== '' ? "{$urlPath}?{$query}" : $urlPath;
            }
        }

        // Ensure path starts with "/"
        if (!str_starts_with($cleaned, '/')) {
            $cleaned = '/' . $cleaned;
        }

        if ($this->config->stripExtensions) {
            $cleaned = $this->stripExtension($cleaned);
        }

        if ($this->config->stripQueryParameters) {
            $cleaned = $this->stripQueryParameters($cleaned);
        }

        return $cleaned;
    }

    private function stripExtension(string $path): string
    {
        $questionPos = strpos($path, '?');
        if ($questionPos !== false) {
            $pathPart = substr($path, 0, $questionPos);
            $query = substr($path, $questionPos + 1);
        } else {
            $pathPart = $path;
            $query = null;
        }

        $lastSlash = strrpos($pathPart, '/');
        $dir = $lastSlash !== false ? substr($pathPart, 0, $lastSlash) : '';
        $base = $lastSlash !== false ? substr($pathPart, $lastSlash + 1) : $pathPart;

        // Don't strip dotfiles like ".hidden" or ".env"
        if (str_starts_with($base, '.')) {
            return $path;
        }

        $dotPos = strpos($base, '.');
        if ($dotPos === false) {
            return $path; // no extension to strip
        }

        $stripped = substr($base, 0, $dotPos);
        $result = $dir !== '' ? "{$dir}/{$stripped}" : "/{$stripped}";
        if ($result === '') {
            $result = '/';
        }

        return $query !== null ? "{$result}?{$query}" : $result;
    }

    private function stripQueryParameters(string $path): string
    {
        $questionPos = strpos($path, '?');
        if ($questionPos !== false) {
            return substr($path, 0, $questionPos);
        }

        return $path;
    }

    private function applyConfiguredImageDefaults(array $params): array
    {
        $defaults = [
            'image_type' => $this->config->imageType,
            'quality' => $this->config->quality,
            'max_bytes' => $this->config->maxBytes,
        ];

        foreach ($defaults as $key => $value) {
            if ($value === null || array_key_exists($key, $params)) {
                continue;
            }

            $params[$key] = $value;
        }

        return $params;
    }

    private function isStatusPlaceholder(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        return (bool) preg_match('#/status/(?:processing|failed)\.(?:jpg|png)$#', $url);
    }

    private function request(string $url, bool $json, array $headers): array
    {
        $client = $this->getHttpClient();
        $effectiveUrl = null;

        $requestHeaders = $headers;
        if ($json) {
            $requestHeaders['Accept'] = 'application/json';
        }

        try {
            $response = $client->post($url, [
                RequestOptions::HEADERS => $requestHeaders,
                RequestOptions::CONNECT_TIMEOUT => $this->config->connectTimeout,
                RequestOptions::TIMEOUT => $this->config->timeout,
                RequestOptions::ALLOW_REDIRECTS => [
                    'track_redirects' => true,
                ],
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::ON_STATS => function (TransferStats $stats) use (&$effectiveUrl): void {
                    $effectiveUri = $stats->getEffectiveUri();
                    $effectiveUrl = $effectiveUri !== null ? (string) $effectiveUri : null;
                },
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $body = (string) $response->getBody();
                throw new RequestException(
                    "OG Pilot request failed with status {$statusCode}: {$body}",
                    $statusCode
                );
            }

            $redirectHistory = $response->getHeader('X-Guzzle-Redirect-History');
            $historyFinalUrl = !empty($redirectHistory) ? end($redirectHistory) : null;
            $effectiveFinalUrl = ($effectiveUrl !== null && $effectiveUrl !== $url) ? $effectiveUrl : null;

            return [
                'body' => (string) $response->getBody(),
                'location' => $response->getHeaderLine('Location') ?: null,
                'final_url' => $historyFinalUrl ?: $effectiveFinalUrl,
            ];
        } catch (GuzzleException $e) {
            throw new RequestException("OG Pilot request failed: {$e->getMessage()}");
        }
    }

    private function buildUrl(array $params, int|float|DateTimeInterface|null $iat): string
    {
        $payload = $this->buildPayload($params, $iat);
        $token = JwtEncoder::encode($payload, $this->getApiKey());

        $url = rtrim($this->config->baseUrl, '/') . self::ENDPOINT_PATH;
        return $url . '?' . http_build_query(['token' => $token]);
    }

    private function buildPayload(array $params, int|float|DateTimeInterface|null $iat): array
    {
        $payload = $params;

        if ($iat !== null) {
            $payload['iat'] = $this->normalizeIat($iat);
        }

        if (!isset($payload['iss']) || empty($payload['iss'])) {
            $payload['iss'] = $this->getDomain();
        }

        if (!isset($payload['sub']) || empty($payload['sub'])) {
            $payload['sub'] = $this->getApiKeyPrefix();
        }

        $this->validatePayload($payload);

        return $payload;
    }

    private function validatePayload(array $payload): void
    {
        if (empty($payload['iss'])) {
            throw new ConfigurationException('OG Pilot domain is missing');
        }

        if (empty($payload['sub'])) {
            throw new ConfigurationException('OG Pilot API key prefix is missing');
        }

        if (empty($payload['title'])) {
            throw new \InvalidArgumentException('OG Pilot title is required');
        }
    }

    private function getApiKey(): string
    {
        if (!empty($this->config->apiKey)) {
            return $this->config->apiKey;
        }

        throw new ConfigurationException('OG Pilot API key is missing');
    }

    private function getDomain(): string
    {
        if (!empty($this->config->domain)) {
            return $this->config->domain;
        }

        throw new ConfigurationException('OG Pilot domain is missing');
    }

    private function getApiKeyPrefix(): string
    {
        return substr($this->getApiKey(), 0, 8);
    }

    private function normalizeIat(int|float|DateTimeInterface $iat): int
    {
        if ($iat instanceof DateTimeInterface) {
            return $iat->getTimestamp();
        }

        // If timestamp is in milliseconds (> year 5000 in seconds), convert to seconds
        if ($iat > 100000000000) {
            return (int) floor($iat / 1000);
        }

        return (int) floor($iat);
    }

    private function getHttpClient(): HttpClient
    {
        if ($this->httpClient === null) {
            $this->httpClient = new HttpClient();
        }

        return $this->httpClient;
    }

    private function createTemplateImage(string $template, array $params = [], array $options = []): string|array|null
    {
        return $this->createImage(
            array_merge($params, ['template' => $template]),
            $options
        );
    }

    /**
     * Log errors at error level when Laravel logging is available.
     * Falls back to PHP error_log otherwise.
     */
    protected function logError(string $message, array $context = []): void
    {
        if (class_exists(\Illuminate\Support\Facades\Log::class)) {
            try {
                \Illuminate\Support\Facades\Log::error($message, $context);
                return;
            } catch (\Throwable) {
                // Fall through to error_log.
            }
        }

        $encodedContext = $context === [] ? '' : json_encode($context);
        if ($encodedContext === false || $encodedContext === '') {
            error_log($message);
            return;
        }

        error_log("{$message} {$encodedContext}");
    }
}
