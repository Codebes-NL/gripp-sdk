<?php

namespace CodeBes\GrippSdk\Exceptions;

use Exception;

class GrippException extends Exception
{
    protected ?string $rpcMethod;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, ?string $rpcMethod = null)
    {
        parent::__construct($message, $code, $previous);

        $this->rpcMethod = $rpcMethod;
    }

    public function getRpcMethod(): ?string
    {
        return $this->rpcMethod;
    }

    public function toArray(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'rpc_method' => $this->rpcMethod,
        ];
    }
}
