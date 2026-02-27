<?php

namespace CodeBes\GrippSdk\Exceptions;

class RateLimitException extends GrippException
{
    protected ?int $retryAfter;

    protected ?int $remaining;

    public function __construct(string $message = '', ?int $retryAfter = null, ?int $remaining = null, int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->retryAfter = $retryAfter;
        $this->remaining = $remaining;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function getRemaining(): ?int
    {
        return $this->remaining;
    }
}
