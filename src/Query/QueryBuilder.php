<?php

namespace CodeBes\GrippSdk\Query;

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Transport\JsonRpcResponse;
use Illuminate\Support\Collection;

/**
 * Fluent query builder for filtering, ordering, and paginating Gripp resources.
 *
 * Typically accessed via the `where()` method on a resource class, not instantiated directly.
 *
 * Supported filter operators:
 * - equals: Exact match (default for two-argument where)
 * - notequals: Not equal to
 * - contains: String contains substring
 * - notcontains: String does not contain substring
 * - startswith: String starts with prefix
 * - endswith: String ends with suffix
 * - greaterthan: Greater than (works with numbers, dates)
 * - lessthan: Less than
 * - greaterequals: Greater than or equal to
 * - lessequals: Less than or equal to
 * - in: Value is in the given array
 * - notin: Value is not in the given array
 * - isnull: Field is null (pass true as value)
 * - isnotnull: Field is not null (pass true as value)
 *
 * @example
 * // Two-argument where (defaults to 'equals')
 * $companies = Company::where('active', true)->get();
 *
 * // Three-argument where (explicit operator)
 * $results = Company::where('companyname', 'contains', 'Tech')->get();
 *
 * // Chain filters, ordering, and pagination
 * $projects = Project::where('company', 42)
 *     ->where('archived', false)
 *     ->orderBy('createdon', 'desc')
 *     ->limit(25)
 *     ->offset(0)
 *     ->get();
 *
 * // Get first match or count
 * $first = Project::where('name', 'startswith', 'Web')->first();
 * $count = Task::where('project', 10)->count();
 */
class QueryBuilder
{
    /**
     * All supported filter operators.
     */
    public const OPERATORS = [
        'equals',
        'notequals',
        'contains',
        'notcontains',
        'startswith',
        'endswith',
        'greaterthan',
        'lessthan',
        'greaterequals',
        'lessequals',
        'in',
        'notin',
        'isnull',
        'isnotnull',
    ];

    protected string $resourceClass;

    protected string $entity;

    /** @var Filter[] */
    protected array $filters = [];

    protected array $orderBy = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    public function __construct(string $resourceClass, string $entity)
    {
        $this->resourceClass = $resourceClass;
        $this->entity = $entity;
    }

    public function where(string $field, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            // Two-argument form: where('field', 'value') → operator defaults to 'equals'
            $this->filters[] = new Filter($field, 'equals', $operatorOrValue);
        } else {
            $this->filters[] = new Filter($field, $operatorOrValue, $value);
        }

        return $this;
    }

    /**
     * Filter where a date/datetime field falls between two values (inclusive).
     *
     * @param  string $field Date field name (e.g. 'createdon', 'date')
     * @param  string $start Start date/datetime string
     * @param  string $end   End date/datetime string
     */
    public function whereDateBetween(string $field, string $start, string $end): static
    {
        $this->where($field, 'greaterequals', $start);
        $this->where($field, 'lessequals', $end);

        return $this;
    }

    /**
     * Filter where a date/datetime field falls within a given year.
     */
    public function whereYear(string $field, int $year): static
    {
        return $this->whereDateBetween($field, "{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59");
    }

    /**
     * Filter where a date/datetime field falls within a given month.
     */
    public function whereMonth(string $field, int $year, int $month): static
    {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->whereDateBetween($field, "{$startDate} 00:00:00", "{$endDate} 23:59:59");
    }

    /**
     * Filter records modified on or after a given timestamp.
     *
     * Uses the 'updatedon' field by default (standard across all Gripp entities).
     */
    public function whereModifiedSince(\DateTimeInterface $date, string $field = 'updatedon'): static
    {
        return $this->where($field, 'greaterequals', $date->format('Y-m-d H:i:s'));
    }

    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->orderBy[] = [
            'field' => $field,
            'direction' => $direction,
        ];

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function get(): Collection
    {
        $response = $this->execute();

        return $response->toCollection();
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $response = $this->execute();
        $rows = $response->rows();

        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $response = $this->execute();

        return $response->count();
    }

    public function toFilterArray(): array
    {
        return array_map(function (Filter $filter) {
            $array = $filter->toArray();

            // Auto-prefix entity name when the field is unqualified
            if (! str_contains($array['field'], '.')) {
                $array['field'] = $this->entity . '.' . $array['field'];
            }

            return $array;
        }, $this->filters);
    }

    public function toOptionsArray(): array
    {
        $options = [];

        if (! empty($this->orderBy)) {
            $options['orderings'] = $this->orderBy;
        }

        if ($this->limit !== null || $this->offset !== null) {
            $options['paging'] = [];
            if ($this->offset !== null) {
                $options['paging']['firstresult'] = $this->offset;
            }
            if ($this->limit !== null) {
                $options['paging']['maxresults'] = $this->limit;
            }
        }

        return $options;
    }

    protected function execute(): JsonRpcResponse
    {
        $method = $this->entity . '.get';
        $params = [$this->toFilterArray(), $this->toOptionsArray()];
        $transport = GrippClient::getTransport();

        // When an explicit limit is set, use a single call to respect it.
        // Otherwise, auto-paginate to fetch all matching results.
        if ($this->limit !== null) {
            return $transport->call($method, $params);
        }

        return $transport->paginate($method, $params);
    }
}
