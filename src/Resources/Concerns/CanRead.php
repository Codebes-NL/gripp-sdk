<?php

namespace CodeBes\GrippSdk\Resources\Concerns;

use CodeBes\GrippSdk\Query\QueryBuilder;
use Illuminate\Support\Collection;

trait CanRead
{
    public static function get(array $filters = [], array $options = []): Collection
    {
        $response = static::rpcCall('get', [$filters, $options]);

        return $response->toCollection();
    }

    public static function find(int $id): ?array
    {
        $filters = [
            [
                'field' => static::entity() . '.id',
                'operator' => 'equals',
                'value' => $id,
            ],
        ];

        $response = static::rpcCall('getone', [$filters]);
        $rows = $response->rows();

        return $rows[0] ?? null;
    }

    public static function all(): Collection
    {
        $transport = \CodeBes\GrippSdk\GrippClient::getTransport();
        $method = static::entity() . '.get';
        $response = $transport->paginate($method, [[], []]);

        return $response->toCollection();
    }

    public static function where(string $field, mixed $operatorOrValue, mixed $value = null): QueryBuilder
    {
        $builder = new QueryBuilder(static::class, static::entity());

        return $builder->where($field, $operatorOrValue, $value);
    }

    public static function first(array $filters = []): ?array
    {
        $options = ['paging' => ['firstresult' => 0, 'maxresults' => 1]];
        $response = static::rpcCall('get', [$filters, $options]);
        $rows = $response->rows();

        return $rows[0] ?? null;
    }
}
