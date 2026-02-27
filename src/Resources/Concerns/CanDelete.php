<?php

namespace CodeBes\GrippSdk\Resources\Concerns;

trait CanDelete
{
    public static function delete(int $id): array
    {
        $response = static::rpcCall('delete', [$id]);

        return $response->result();
    }
}
