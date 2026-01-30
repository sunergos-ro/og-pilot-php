<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Tests;

use PHPUnit\Framework\TestCase;
use Sunergos\OgPilot\JwtEncoder;

class JwtEncoderTest extends TestCase
{
    public function test_encode_returns_valid_jwt_format(): void
    {
        $payload = [
            'title' => 'Test Title',
            'iss' => 'example.com',
            'sub' => 'test1234',
        ];

        $token = JwtEncoder::encode($payload, 'secret-key');

        // JWT should have three parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Each part should be base64url encoded
        foreach ($parts as $part) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $part);
        }
    }

    public function test_encode_produces_consistent_output(): void
    {
        $payload = [
            'title' => 'Test',
            'iat' => 1234567890,
        ];

        $token1 = JwtEncoder::encode($payload, 'secret');
        $token2 = JwtEncoder::encode($payload, 'secret');

        $this->assertEquals($token1, $token2);
    }
}
