<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\Client;
use Sunergos\OgPilot\Configuration;
use Sunergos\OgPilot\RequestContext;
use ReflectionClass;

class ClientPathResolutionTest extends TestCase
{
    private Configuration $config;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        RequestContext::clearCurrentRequest();

        $this->config = new Configuration([
            'api_key' => 'test_api_key_12345678',
            'domain' => 'example.com',
        ]);
        $this->client = new Client($this->config);
    }

    protected function tearDown(): void
    {
        RequestContext::clearCurrentRequest();
        parent::tearDown();
    }

    // Test resolvePath method

    public function test_resolve_path_returns_manual_path_when_provided(): void
    {
        $path = $this->invokeResolvePath('/manual/path', false);

        $this->assertEquals('/manual/path', $path);
    }

    public function test_resolve_path_normalizes_manual_path_without_leading_slash(): void
    {
        $path = $this->invokeResolvePath('manual/path', false);

        $this->assertEquals('/manual/path', $path);
    }

    public function test_resolve_path_returns_slash_when_default_is_true(): void
    {
        RequestContext::setCurrentRequest(['url' => '/should/be/ignored']);

        $path = $this->invokeResolvePath(null, true);

        $this->assertEquals('/', $path);
    }

    public function test_resolve_path_uses_request_context_when_available(): void
    {
        RequestContext::setCurrentRequest(['url' => '/context/path?query=1']);

        $path = $this->invokeResolvePath(null, false);

        $this->assertEquals('/context/path?query=1', $path);
    }

    public function test_resolve_path_returns_slash_when_no_path_available(): void
    {
        $path = $this->invokeResolvePath(null, false);

        $this->assertEquals('/', $path);
    }

    public function test_resolve_path_manual_wins_over_context(): void
    {
        RequestContext::setCurrentRequest(['url' => '/context/path']);

        $path = $this->invokeResolvePath('/manual/path', false);

        $this->assertEquals('/manual/path', $path);
    }

    public function test_resolve_path_manual_wins_over_default(): void
    {
        $path = $this->invokeResolvePath('/manual/path', true);

        $this->assertEquals('/manual/path', $path);
    }

    // Test normalizePath method

    public function test_normalize_path_returns_slash_for_null(): void
    {
        $path = $this->invokeNormalizePath(null);

        $this->assertEquals('/', $path);
    }

    public function test_normalize_path_returns_slash_for_empty_string(): void
    {
        $path = $this->invokeNormalizePath('');

        $this->assertEquals('/', $path);
    }

    public function test_normalize_path_adds_leading_slash(): void
    {
        $path = $this->invokeNormalizePath('foo/bar');

        $this->assertEquals('/foo/bar', $path);
    }

    public function test_normalize_path_keeps_existing_leading_slash(): void
    {
        $path = $this->invokeNormalizePath('/foo/bar');

        $this->assertEquals('/foo/bar', $path);
    }

    public function test_normalize_path_extracts_from_full_url(): void
    {
        $path = $this->invokeNormalizePath('https://example.com/foo/bar?query=1');

        $this->assertEquals('/foo/bar?query=1', $path);
    }

    public function test_normalize_path_extracts_from_http_url(): void
    {
        $path = $this->invokeNormalizePath('http://example.com/path');

        $this->assertEquals('/path', $path);
    }

    public function test_normalize_path_handles_url_without_path(): void
    {
        $path = $this->invokeNormalizePath('https://example.com');

        $this->assertEquals('/', $path);
    }

    public function test_normalize_path_trims_whitespace(): void
    {
        $path = $this->invokeNormalizePath('  /foo/bar  ');

        $this->assertEquals('/foo/bar', $path);
    }

    // Helper methods to invoke private methods

    private function invokeResolvePath(mixed $manualPath, bool $useDefault): string
    {
        $reflection = new ReflectionClass($this->client);
        $method = $reflection->getMethod('resolvePath');
        $method->setAccessible(true);

        return $method->invoke($this->client, $manualPath, $useDefault);
    }

    private function invokeNormalizePath(?string $path): string
    {
        $reflection = new ReflectionClass($this->client);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        return $method->invoke($this->client, $path);
    }
}
