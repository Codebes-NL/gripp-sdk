<?php

namespace CodeBes\GrippSdk\Query;

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Transport\JsonRpcClient;
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

    /**
     * Set by whereModifiedSince(). Holds [DateTimeInterface, updatedField, createdField].
     *
     * Not a plain filter, because it cannot be expressed as one - see
     * whereModifiedSince() for why, and execute() for how it is resolved.
     *
     * @var array{0: \DateTimeInterface, 1: string, 2: string}|null
     */
    protected ?array $modifiedSince = null;

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
     * Filter records created or modified on or after a given timestamp.
     *
     * **This is not a single filter, and it cannot be.** Gripp leaves `updatedon`
     * empty on records that were created and never edited afterwards, so a plain
     * `updatedon >= $date` silently omits every record that has never been touched
     * since it was written. Measured against a live account in September 2026: of
     * the hour rows an incremental sync failed to pick up, 749 out of 749 had an
     * empty `updatedon`, while all 889 rows it did pick up had one. Those were all
     * draft hours - written once, never edited until approval - so they stayed
     * invisible for months and the totals silently drifted from Gripp's own report.
     *
     * The correct condition is `updatedon >= $date OR createdon >= $date`, and the
     * Gripp API has no OR: every filter in the array is ANDed. So execute() runs the
     * two halves as separate queries and merges them on `id`. That costs one extra
     * round trip per page, which is the price of a correct answer.
     *
     * @param  string $field        Modification timestamp field.
     * @param  string $createdField Creation timestamp field, queried alongside it.
     */
    public function whereModifiedSince(
        \DateTimeInterface $date,
        string $field = 'updatedon',
        string $createdField = 'createdon'
    ): static {
        $this->modifiedSince = [$date, $field, $createdField];

        return $this;
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
        $transport = GrippClient::getTransport();
        $options = $this->toOptionsArray();

        if ($this->modifiedSince !== null) {
            return $this->executeModifiedSince($method, $transport, $options);
        }

        $params = [$this->toFilterArray(), $options];

        // When an explicit limit is set, use a single call to respect it.
        // Otherwise, auto-paginate to fetch all matching results.
        if ($this->limit !== null) {
            return $transport->call($method, $params);
        }

        return $transport->paginate($method, $params);
    }

    /**
     * Resolve whereModifiedSince() as the union of two queries.
     *
     * One half matches records edited since the timestamp, the other records
     * created since it. A record that was created and then edited matches both,
     * so the halves are merged on `id` rather than concatenated.
     */
    protected function executeModifiedSince(string $method, JsonRpcClient $transport, array $options): JsonRpcResponse
    {
        [$date, $updatedField, $createdField] = $this->modifiedSince;
        $timestamp = $date->format('Y-m-d H:i:s');
        $baseFilters = $this->toFilterArray();

        $byId = [];
        foreach ([$updatedField, $createdField] as $field) {
            $filters = $baseFilters;
            $filters[] = $this->qualify([
                'field' => $field,
                'operator' => 'greaterequals',
                'value' => $timestamp,
            ]);

            $params = [$filters, $options];
            $response = $this->limit !== null
                ? $transport->call($method, $params)
                : $transport->paginate($method, $params);

            foreach ($response->rows() as $row) {
                // Rows without an id cannot be deduplicated; keep them all.
                $key = $row['id'] ?? count($byId);
                $byId[$key] = $row;
            }
        }

        $rows = array_values($byId);

        // An explicit limit applies to the union, not to each half.
        if ($this->limit !== null) {
            $rows = array_slice($rows, 0, $this->limit);
        }

        return new JsonRpcResponse([
            'id' => 0,
            'result' => [
                'rows' => $rows,
                'count' => count($rows),
                'start' => 0,
                'more_items_in_collection' => false,
            ],
        ]);
    }

    /**
     * Apply the same entity prefixing toFilterArray() does to a raw filter array.
     */
    protected function qualify(array $filter): array
    {
        if (! str_contains($filter['field'], '.')) {
            $filter['field'] = $this->entity . '.' . $filter['field'];
        }

        return $filter;
    }
}
