<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\Configuration;

class ConfigurationTest extends TestCase
{
    public function test_configuration_has_default_values(): void
    {
        $config = new Configuration();

        $this->assertEquals('https://ogpilot.com', $config->baseUrl);
        $this->assertEquals(5.0, $config->connectTimeout);
        $this->assertEquals(10.0, $config->timeout);
    }

    public function test_configuration_accepts_custom_values(): void
    {
        $config = new Configuration([
            'api_key' => 'test-api-key',
            'domain' => 'example.com',
            'base_url' => 'https://custom.ogpilot.com',
            'connect_timeout' => 3.0,
            'timeout' => 8.0,
        ]);

        $this->assertEquals('test-api-key', $config->apiKey);
        $this->assertEquals('example.com', $config->domain);
        $this->assertEquals('https://custom.ogpilot.com', $config->baseUrl);
        $this->assertEquals(3.0, $config->connectTimeout);
        $this->assertEquals(8.0, $config->timeout);
    }
}
