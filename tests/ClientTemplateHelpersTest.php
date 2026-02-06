<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\Client;
use Sunergos\OgPilot\Configuration;

class ClientTemplateHelpersTest extends TestCase
{
    public function test_template_helpers_force_expected_template(): void
    {
        $config = new Configuration([
            'api_key' => 'test_api_key_12345678',
            'domain' => 'example.com',
        ]);

        $client = new class ($config) extends Client {
            public array $calls = [];

            public function createImage(array $params = [], array $options = []): string|array
            {
                $this->calls[] = [
                    'params' => $params,
                    'options' => $options,
                ];

                return 'ok';
            }
        };

        $helpers = [
            'createBlogPostImage' => 'blog_post',
            'createPodcastImage' => 'podcast',
            'createProductImage' => 'product',
            'createEventImage' => 'event',
            'createBookImage' => 'book',
            'createCompanyImage' => 'company',
            'createPortfolioImage' => 'portfolio',
        ];

        foreach ($helpers as $method => $expectedTemplate) {
            $result = $client->$method(
                ['title' => 'Hello', 'template' => 'page'],
                ['json' => true]
            );

            $this->assertSame('ok', $result);

            $call = $client->calls[count($client->calls) - 1];
            $this->assertSame('Hello', $call['params']['title']);
            $this->assertSame($expectedTemplate, $call['params']['template']);
            $this->assertSame(['json' => true], $call['options']);
        }
    }
}
