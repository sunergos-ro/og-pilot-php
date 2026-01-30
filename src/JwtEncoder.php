<?php

declare(strict_types=1);

namespace Sunergos\OgPilot;

use Firebase\JWT\JWT;

class JwtEncoder
{
    private const ALGORITHM = 'HS256';

    public static function encode(array $payload, string $secret): string
    {
        return JWT::encode($payload, $secret, self::ALGORITHM);
    }
}
