<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\OgPilot;
use Sunergos\OgPilot\Client;
use Sunergos\OgPilot\Configuration;

class OgPilotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OgPilot::resetConfig();
    }

    public function test_config_returns_configuration_instance(): void
    {
        $config = OgPilot::config();

        $this->assertInstanceOf(Configuration::class, $config);
    }

    public function test_set_config_updates_configuration(): void
    {
        OgPilot::setConfig([
            'api_key' => 'my-api-key',
            'domain' => 'test.com',
        ]);

        $config = OgPilot::config();

        $this->assertEquals('my-api-key', $config->apiKey);
        $this->assertEquals('test.com', $config->domain);
    }

    public function test_configure_allows_callback_style_configuration(): void
    {
        OgPilot::configure(function ($config) {
            $config->apiKey = 'callback-key';
            $config->domain = 'callback.com';
        });

        $config = OgPilot::config();

        $this->assertEquals('callback-key', $config->apiKey);
        $this->assertEquals('callback.com', $config->domain);
    }

    public function test_client_returns_client_instance(): void
    {
        OgPilot::setConfig([
            'api_key' => 'test-key',
            'domain' => 'test.com',
        ]);

        $client = OgPilot::client();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_create_client_returns_new_client_with_custom_config(): void
    {
        $client = OgPilot::createClient([
            'api_key' => 'custom-key',
            'domain' => 'custom.com',
        ]);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_reset_config_clears_configuration(): void
    {
        OgPilot::setConfig([
            'api_key' => 'test-key',
        ]);

        OgPilot::resetConfig();

        $config = OgPilot::config();

        $this->assertNull($config->apiKey);
    }
}
