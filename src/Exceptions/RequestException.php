<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Exceptions;

class RequestException extends OgPilotException
{
    protected ?int $statusCode;

    public function __construct(string $message, ?int $statusCode = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
