<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\Client;
use Sunergos\OgPilot\Configuration;
use Sunergos\OgPilot\RequestContext;
use Sunergos\OgPilot\Exceptions\ConfigurationException;
use Sunergos\OgPilot\Exceptions\RequestException;

class ClientRequestTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        parent::setUp();
        RequestContext::clearCurrentRequest();

        $this->config = new Configuration([
            'api_key' => 'test_api_key_12345678',
            'domain' => 'example.com',
            'base_url' => 'https://ogpilot.com',
        ]);
    }

    protected function tearDown(): void
    {
        RequestContext::clearCurrentRequest();
        parent::tearDown();
    }

    public function test_create_image_follows_redirects_and_returns_final_url(): void
    {
        $history = [];
        $client = $this->makeClient(
            [
                new Response(302, ['Location' => 'https://cdn.ogpilot.com/generated.png']),
                new Response(200, [], ''),
            ],
            $history
        );

        $result = $client->createImage([
            'title' => 'Hello OG Pilot',
            'path' => '/articles/test',
        ]);

        $this->assertSame('https://cdn.ogpilot.com/generated.png', $result);
        $this->assertCount(2, $history);

        $initialRequest = $history[0]['request'];
        $this->assertSame('POST', $initialRequest->getMethod());
        $this->assertSame('/api/v1/images', $initialRequest->getUri()->getPath());

        parse_str($initialRequest->getUri()->getQuery(), $query);
        $this->assertArrayHasKey('token', $query);

        $payload = JWT::decode($query['token'], new Key($this->config->apiKey, 'HS256'));
        $this->assertSame('Hello OG Pilot', $payload->title);
        $this->assertSame('/articles/test', $payload->path);
        $this->assertSame('example.com', $payload->iss);
        $this->assertSame('test_api', $payload->sub);

        $redirectRequest = $history[1]['request'];
        $this->assertSame('GET', $redirectRequest->getMethod());
        $this->assertSame('https://cdn.ogpilot.com/generated.png', (string) $redirectRequest->getUri());
    }

    public function test_create_image_falls_back_to_location_header_when_final_url_is_unavailable(): void
    {
        $history = [];
        $client = $this->makeClientWithoutRedirectMiddleware(
            [new Response(302, ['Location' => 'https://cdn.ogpilot.com/fallback.png'])],
            $history
        );

        $result = $client->createImage([
            'title' => 'Fallback Location',
            'path' => '/fallback/location',
        ]);

        $this->assertSame('https://cdn.ogpilot.com/fallback.png', $result);
        $this->assertCount(1, $history);
    }

    public function test_create_image_with_json_option_posts_and_returns_decoded_json(): void
    {
        $history = [];
        $client = $this->makeClient(
            [new Response(200, [], '{"ok":true,"url":"https://cdn.ogpilot.com/image.png"}')],
            $history
        );

        $result = $client->createImage([
            'title' => 'JSON Mode',
            'path' => '/docs/readme',
        ], [
            'json' => true,
        ]);

        $this->assertSame([
            'ok' => true,
            'url' => 'https://cdn.ogpilot.com/image.png',
        ], $result);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function test_create_image_returns_signed_url_when_location_header_is_missing(): void
    {
        $history = [];
        $client = $this->makeClient([new Response(200, [], '')], $history);

        $result = $client->createImage([
            'title' => 'Fallback URL',
            'path' => '/fallback/path',
        ]);

        $this->assertIsString($result);
        $this->assertStringStartsWith('https://ogpilot.com/api/v1/images?token=', $result);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/images', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $requestQuery);
        parse_str((string) parse_url($result, PHP_URL_QUERY), $resultQuery);

        $this->assertSame($requestQuery['token'] ?? null, $resultQuery['token'] ?? null);
    }

    public function test_create_image_returns_null_and_logs_error_when_validation_fails_in_url_mode(): void
    {
        $client = new LoggingClient($this->config);

        $result = $client->createImage([
            'path' => '/missing/title',
        ]);

        $this->assertNull($result);
        $this->assertCount(1, $client->loggedErrors);
        $this->assertStringContainsString('OG Pilot createImage failed:', $client->loggedErrors[0]['message']);
        $this->assertSame(\InvalidArgumentException::class, $client->loggedErrors[0]['context']['exception'] ?? null);
        $this->assertFalse($client->loggedErrors[0]['context']['json'] ?? true);
    }

    public function test_create_image_returns_null_and_logs_error_when_request_fails_in_url_mode(): void
    {
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(500, [], '{"error":"upstream failure"}'),
            ])),
        ]);

        $client = new LoggingClient($this->config, $httpClient);

        $result = $client->createImage([
            'title' => 'Request Failure URL Mode',
            'path' => '/request/failure/url',
        ]);

        $this->assertNull($result);
        $this->assertCount(1, $client->loggedErrors);
        $this->assertStringContainsString('OG Pilot createImage failed:', $client->loggedErrors[0]['message']);
        $this->assertSame(RequestException::class, $client->loggedErrors[0]['context']['exception'] ?? null);
        $this->assertFalse($client->loggedErrors[0]['context']['json'] ?? true);
    }

    public function test_create_image_returns_fallback_array_and_logs_error_when_request_fails_in_json_mode(): void
    {
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(500, [], '{"error":"upstream failure"}'),
            ])),
        ]);

        $client = new LoggingClient($this->config, $httpClient);

        $result = $client->createImage([
            'title' => 'Request Failure JSON Mode',
            'path' => '/request/failure/json',
        ], [
            'json' => true,
        ]);

        $this->assertSame(['image_url' => null], $result);
        $this->assertCount(1, $client->loggedErrors);
        $this->assertStringContainsString('OG Pilot createImage failed:', $client->loggedErrors[0]['message']);
        $this->assertSame(RequestException::class, $client->loggedErrors[0]['context']['exception'] ?? null);
        $this->assertTrue($client->loggedErrors[0]['context']['json'] ?? false);
    }

    public function test_create_image_returns_fallback_array_and_logs_error_when_json_response_is_invalid(): void
    {
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, [], 'not-json'),
            ])),
        ]);

        $client = new LoggingClient($this->config, $httpClient);

        $result = $client->createImage([
            'title' => 'Invalid JSON response',
            'path' => '/invalid/json',
        ], [
            'json' => true,
        ]);

        $this->assertSame(['image_url' => null], $result);
        $this->assertCount(1, $client->loggedErrors);
        $this->assertStringContainsString('invalid JSON response body', $client->loggedErrors[0]['message']);
        $this->assertSame(RequestException::class, $client->loggedErrors[0]['context']['exception'] ?? null);
        $this->assertTrue($client->loggedErrors[0]['context']['json'] ?? false);
    }

    public function test_create_image_returns_fallback_array_and_logs_error_when_configuration_fails_in_json_mode(): void
    {
        $config = new Configuration([
            'domain' => 'example.com',
        ]);

        $client = new LoggingClient($config);

        $result = $client->createImage([
            'title' => 'Missing API key',
        ], [
            'json' => true,
        ]);

        $this->assertSame(['image_url' => null], $result);
        $this->assertCount(1, $client->loggedErrors);
        $this->assertStringContainsString('OG Pilot createImage failed:', $client->loggedErrors[0]['message']);
        $this->assertSame(ConfigurationException::class, $client->loggedErrors[0]['context']['exception'] ?? null);
        $this->assertTrue($client->loggedErrors[0]['context']['json'] ?? false);
    }

    private function makeClient(array $responses, array &$history): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));

        $httpClient = new HttpClient([
            'handler' => $handlerStack,
        ]);

        return new Client($this->config, $httpClient);
    }

    private function makeClientWithoutRedirectMiddleware(array $responses, array &$history): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = new HandlerStack($mock);
        $handlerStack->push(Middleware::history($history));

        $httpClient = new HttpClient([
            'handler' => $handlerStack,
        ]);

        return new Client($this->config, $httpClient);
    }
}

class LoggingClient extends Client
{
    /** @var array<int, array{message: string, context: array}> */
    public array $loggedErrors = [];

    protected function logError(string $message, array $context = []): void
    {
        $this->loggedErrors[] = [
            'message' => $message,
            'context' => $context,
        ];
    }
}
