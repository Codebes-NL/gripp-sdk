<?php

namespace CodeBes\GrippSdk\Exceptions;

class AuthenticationException extends GrippException
{
    protected int $statusCode;

    public function __construct(string $message = '', int $statusCode = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function isTokenInvalid(): bool
    {
        return $this->statusCode === 401;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }
}
