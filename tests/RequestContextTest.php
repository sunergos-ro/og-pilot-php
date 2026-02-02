<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\RequestContext;

class RequestContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RequestContext::clearCurrentRequest();
    }

    protected function tearDown(): void
    {
        RequestContext::clearCurrentRequest();
        parent::tearDown();
    }

    public function test_set_current_request_stores_request(): void
    {
        RequestContext::setCurrentRequest(['url' => '/test/path']);

        $request = RequestContext::getCurrentRequest();

        $this->assertEquals(['url' => '/test/path'], $request);
    }

    public function test_clear_current_request_removes_request(): void
    {
        RequestContext::setCurrentRequest(['url' => '/test/path']);
        RequestContext::clearCurrentRequest();

        $request = RequestContext::getCurrentRequest();

        $this->assertNull($request);
    }

    public function test_get_current_path_returns_explicit_path(): void
    {
        RequestContext::setCurrentRequest([
            'url' => '/ignored',
            'path' => '/explicit/path'
        ]);

        $path = RequestContext::getCurrentPath();

        $this->assertEquals('/explicit/path', $path);
    }

    public function test_get_current_path_extracts_from_url(): void
    {
        RequestContext::setCurrentRequest([
            'url' => '/from/url?query=value'
        ]);

        $path = RequestContext::getCurrentPath();

        $this->assertEquals('/from/url?query=value', $path);
    }

    public function test_get_current_path_extracts_from_full_url(): void
    {
        RequestContext::setCurrentRequest([
            'url' => 'https://example.com/path/here?foo=bar'
        ]);

        $path = RequestContext::getCurrentPath();

        $this->assertEquals('/path/here?foo=bar', $path);
    }

    public function test_get_current_path_handles_full_url_without_query(): void
    {
        RequestContext::setCurrentRequest([
            'url' => 'https://example.com/path/here'
        ]);

        $path = RequestContext::getCurrentPath();

        $this->assertEquals('/path/here', $path);
    }

    public function test_with_request_context_runs_callback_with_context(): void
    {
        $capturedPath = null;

        RequestContext::withRequestContext(
            ['url' => '/context/path'],
            function () use (&$capturedPath) {
                $capturedPath = RequestContext::getCurrentPath();
            }
        );

        $this->assertEquals('/context/path', $capturedPath);
    }

    public function test_with_request_context_clears_after_callback(): void
    {
        RequestContext::withRequestContext(
            ['url' => '/temp/path'],
            fn() => null
        );

        $request = RequestContext::getCurrentRequest();

        $this->assertNull($request);
    }

    public function test_with_request_context_restores_previous_context(): void
    {
        RequestContext::setCurrentRequest(['url' => '/original']);

        RequestContext::withRequestContext(
            ['url' => '/nested'],
            fn() => null
        );

        $path = RequestContext::getCurrentPath();

        $this->assertEquals('/original', $path);
    }

    public function test_with_request_context_returns_callback_result(): void
    {
        $result = RequestContext::withRequestContext(
            ['url' => '/test'],
            fn() => 'callback-result'
        );

        $this->assertEquals('callback-result', $result);
    }

    public function test_with_request_context_restores_on_exception(): void
    {
        RequestContext::setCurrentRequest(['url' => '/original']);

        try {
            RequestContext::withRequestContext(
                ['url' => '/nested'],
                fn() => throw new \RuntimeException('Test exception')
            );
        } catch (\RuntimeException) {
            // Expected
        }

        $path = RequestContext::getCurrentPath();

        $this->assertEquals('/original', $path);
    }
}
