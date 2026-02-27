<?php

namespace CodeBes\GrippSdk\Resources\Concerns;

trait CanUpdate
{
    public static function update(int $id, array $fields): array
    {
        $response = static::rpcCall('update', [$id, $fields]);

        return $response->result();
    }
}
