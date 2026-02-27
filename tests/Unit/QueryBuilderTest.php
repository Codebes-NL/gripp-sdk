<?php

namespace CodeBes\GrippSdk\Tests\Unit;

use CodeBes\GrippSdk\Query\Filter;
use CodeBes\GrippSdk\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    public function test_filter_value_object(): void
    {
        $filter = new Filter('company.id', 'equals', 123);

        $this->assertEquals('company.id', $filter->getField());
        $this->assertEquals('equals', $filter->getOperator());
        $this->assertEquals(123, $filter->getValue());
        $this->assertEquals([
            'field' => 'company.id',
            'operator' => 'equals',
            'value' => 123,
        ], $filter->toArray());
    }

    public function test_where_with_three_arguments(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->where('company.name', 'like', '%Acme%');

        $filters = $builder->toFilterArray();

        $this->assertCount(1, $filters);
        $this->assertEquals([
            'field' => 'company.name',
            'operator' => 'like',
            'value' => '%Acme%',
        ], $filters[0]);
    }

    public function test_where_with_two_arguments_defaults_to_equals(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->where('company.id', 123);

        $filters = $builder->toFilterArray();

        $this->assertCount(1, $filters);
        $this->assertEquals([
            'field' => 'company.id',
            'operator' => 'equals',
            'value' => 123,
        ], $filters[0]);
    }

    public function test_multiple_where_clauses(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->where('company.name', 'like', '%Acme%')
            ->where('company.type', 'equals', 'COMPANY');

        $filters = $builder->toFilterArray();

        $this->assertCount(2, $filters);
    }

    public function test_order_by(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->orderBy('company.name', 'asc');

        $options = $builder->toOptionsArray();

        $this->assertEquals([
            'orderings' => [
                ['field' => 'company.name', 'direction' => 'asc'],
            ],
        ], $options);
    }

    public function test_order_by_defaults_to_asc(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->orderBy('company.name');

        $options = $builder->toOptionsArray();

        $this->assertEquals('asc', $options['orderings'][0]['direction']);
    }

    public function test_multiple_order_by(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->orderBy('company.name', 'asc')
            ->orderBy('company.id', 'desc');

        $options = $builder->toOptionsArray();

        $this->assertCount(2, $options['orderings']);
    }

    public function test_limit_and_offset(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');
        $builder->limit(50)->offset(10);

        $options = $builder->toOptionsArray();

        $this->assertEquals([
            'paging' => [
                'firstresult' => 10,
                'maxresults' => 50,
            ],
        ], $options);
    }

    public function test_empty_options_when_no_ordering_or_paging(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');

        $this->assertEquals([], $builder->toOptionsArray());
    }

    public function test_chaining_returns_same_instance(): void
    {
        $builder = new QueryBuilder('TestResource', 'company');

        $result = $builder->where('field', 'value')
            ->orderBy('field')
            ->limit(10)
            ->offset(0);

        $this->assertSame($builder, $result);
    }

    public function test_combined_filters_and_options(): void
    {
        $builder = new QueryBuilder('TestResource', 'task');
        $builder->where('task.date', 'greaterequals', '2026-01-01')
            ->where('task.status', 'equals', 'open')
            ->orderBy('task.date', 'asc')
            ->limit(100)
            ->offset(0);

        $filters = $builder->toFilterArray();
        $options = $builder->toOptionsArray();

        $this->assertCount(2, $filters);
        $this->assertEquals('task.date', $filters[0]['field']);
        $this->assertEquals('greaterequals', $filters[0]['operator']);
        $this->assertArrayHasKey('orderings', $options);
        $this->assertArrayHasKey('paging', $options);
        $this->assertEquals(100, $options['paging']['maxresults']);
    }
}
