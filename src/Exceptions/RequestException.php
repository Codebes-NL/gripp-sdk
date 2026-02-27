<?php

namespace CodeBes\GrippSdk\Exceptions;

class RequestException extends GrippException
{
    protected ?array $responseData;

    public function __construct(string $message = '', ?array $responseData = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->responseData = $responseData;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
