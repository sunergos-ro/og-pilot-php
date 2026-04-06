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
        $this->assertFalse($config->stripQueryParameters);
        $this->assertNull($config->imageType);
        $this->assertNull($config->quality);
        $this->assertNull($config->maxBytes);
    }

    public function test_configuration_accepts_custom_values(): void
    {
        $config = new Configuration([
            'api_key' => 'test-api-key',
            'domain' => 'example.com',
            'base_url' => 'https://custom.ogpilot.com',
            'connect_timeout' => 3.0,
            'timeout' => 8.0,
            'strip_query_parameters' => true,
            'image_type' => 'webp',
            'quality' => 82,
            'max_bytes' => 220000,
        ]);

        $this->assertEquals('test-api-key', $config->apiKey);
        $this->assertEquals('example.com', $config->domain);
        $this->assertEquals('https://custom.ogpilot.com', $config->baseUrl);
        $this->assertEquals(3.0, $config->connectTimeout);
        $this->assertEquals(8.0, $config->timeout);
        $this->assertTrue($config->stripQueryParameters);
        $this->assertSame('webp', $config->imageType);
        $this->assertSame(82, $config->quality);
        $this->assertSame(220000, $config->maxBytes);
    }
}
