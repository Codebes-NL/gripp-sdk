<?php

namespace CodeBes\GrippSdk\Resources\Concerns;

trait CanCreate
{
    public static function create(array $fields): array
    {
        $response = static::rpcCall('create', [$fields]);

        return $response->result();
    }
}
