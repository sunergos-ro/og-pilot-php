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

    public function test_create_image_posts_to_images_endpoint_and_returns_location_header(): void
    {
        $history = [];
        $client = $this->makeClient(
            [new Response(302, ['Location' => 'https://cdn.ogpilot.com/generated.png'])],
            $history
        );

        $result = $client->createImage([
            'title' => 'Hello OG Pilot',
            'path' => '/articles/test',
        ]);

        $this->assertSame('https://cdn.ogpilot.com/generated.png', $result);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/images', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertArrayHasKey('token', $query);

        $payload = JWT::decode($query['token'], new Key($this->config->apiKey, 'HS256'));
        $this->assertSame('Hello OG Pilot', $payload->title);
        $this->assertSame('/articles/test', $payload->path);
        $this->assertSame('example.com', $payload->iss);
        $this->assertSame('test_api', $payload->sub);
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
}
