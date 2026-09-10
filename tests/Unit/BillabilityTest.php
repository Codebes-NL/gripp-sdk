<?php

namespace CodeBes\GrippSdk\Tests\Unit;

use CodeBes\GrippSdk\Features\Billability;
use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Transport\JsonRpcClient;
use CodeBes\GrippSdk\Transport\JsonRpcResponse;
use PHPUnit\Framework\TestCase;

class BillabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        GrippClient::reset();
    }

    /** @var list<array{method: string, filters: array}> */
    private array $calls = [];

    /**
     * Fake the Gripp transport. An entry answers calls to its method. An entry with a
     * 'filter' only answers calls that filter on that field, and wins over a plain one:
     * invoiceability asks hour.get twice, for the period and for the whole life of the
     * Fixed lines in it.
     */
    private function mockTransport(array $callMap): void
    {
        $this->calls = [];
        $mock = $this->createMock(JsonRpcClient::class);
        $mock->method('paginate')
            ->willReturnCallback(function (string $method, array $params) use ($callMap) {
                $filters = $params[0] ?? [];
                $this->calls[] = ['method' => $method, 'filters' => $filters];
                $fields = array_column($filters, 'field');

                $match = null;
                foreach ($callMap as $entry) {
                    if ($entry['method'] !== $method) {
                        continue;
                    }
                    if (isset($entry['filter'])) {
                        if (in_array($entry['filter'], $fields, true)) {
                            $match = $entry;

                            break;
                        }

                        continue;
                    }
                    $match ??= $entry;
                }

                return new JsonRpcResponse([
                    'id' => 1,
                    'result' => [
                        'rows' => $match['rows'] ?? [],
                        'count' => count($match['rows'] ?? []),
                        'more_items_in_collection' => false,
                    ],
                ]);
            });

        GrippClient::setTransport($mock);
    }

    /** @return list<string> */
    private function calledMethods(): array
    {
        return array_column($this->calls, 'method');
    }

    private static function hourRow(int $id, float $amount, int $employee, int $line, ?int $invoiceLine = null, int $project = 99): array
    {
        return [
            'id' => $id,
            'amount' => $amount,
            'employee' => ['id' => $employee],
            'offerprojectbase' => ['id' => $project],
            'offerprojectline' => ['id' => $line],
            'status' => ['id' => 3],
            'invoiceline' => $invoiceLine === null ? null : ['id' => $invoiceLine],
            'date' => ['date' => '2026-01-05 00:00:00'],
        ];
    }

    private static function invoiceLineRow(int $id, int $part, float $amount, int $unit = 1, float $price = 95.0): array
    {
        return ['id' => $id, 'part' => ['id' => $part], 'amount' => $amount, 'unit' => ['id' => $unit], 'sellingprice' => $price];
    }

    // --- forEmployee ---

    public function test_for_employee_classifies_billable_and_non_billable(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    ['id' => 2, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => ['id' => 5], 'date' => ['date' => '2026-01-06 00:00:00']],
                    ['id' => 3, 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 101], 'offerprojectline' => ['id' => 11], 'status' => ['id' => 3], 'invoiceline' => null, 'date' => ['date' => '2026-01-07 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 2]],
                    ['id' => 11, 'invoicebasis' => ['id' => 4]],
                ],
            ],
        ]);

        $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(42, $result['employee_id']);
        $this->assertEquals('2026-01-01', $result['from']);
        $this->assertEquals('2026-01-31', $result['to']);
        $this->assertEquals(20.0, $result['total_hours']);
        $this->assertEquals(16.0, $result['billable_hours']);
        $this->assertEquals(4.0, $result['non_billable_hours']);
        $this->assertEquals(80.0, $result['billability_percentage']);
        // Uninvoiced: hour 1 (status 2, no invoiceline) + hour 3 (status 3, no invoiceline)
        $this->assertEquals(12.0, $result['uninvoiced_hours']);
    }

    public function test_for_employee_groups_by_project(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    ['id' => 2, 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 101], 'offerprojectline' => ['id' => 11], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-06 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 1]],
                    ['id' => 11, 'invoicebasis' => ['id' => 3]],
                ],
            ],
        ]);

        $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertCount(2, $result['by_project']);
        $this->assertEquals(99, $result['by_project'][0]['offerprojectbase_id']);
        $this->assertEquals(8.0, $result['by_project'][0]['total_hours']);
        $this->assertEquals(8.0, $result['by_project'][0]['billable_hours']);
        $this->assertEquals(101, $result['by_project'][1]['offerprojectbase_id']);
        $this->assertEquals(4.0, $result['by_project'][1]['total_hours']);
        $this->assertEquals(4.0, $result['by_project'][1]['billable_hours']);
    }

    public function test_for_employee_with_zero_hours(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'rows' => []],
        ]);

        $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(0.0, $result['total_hours']);
        $this->assertEquals(0.0, $result['billable_hours']);
        $this->assertEquals(0.0, $result['non_billable_hours']);
        $this->assertEquals(0.0, $result['billability_percentage']);
        $this->assertEquals(0.0, $result['uninvoiced_hours']);
        $this->assertEmpty($result['by_project']);
    }

    public function test_for_employee_hours_without_line_are_non_billable(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => null, 'status' => ['id' => 1], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                ],
            ],
        ]);

        $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(8.0, $result['total_hours']);
        $this->assertEquals(0.0, $result['billable_hours']);
        $this->assertEquals(8.0, $result['non_billable_hours']);
        $this->assertEquals(0.0, $result['billability_percentage']);
        // Status 1 (Concept) should not count as uninvoiced
        $this->assertEquals(0.0, $result['uninvoiced_hours']);
    }

    // --- forTeam ---

    public function test_for_team_aggregates_multiple_employees(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    ['id' => 2, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => ['id' => 5], 'date' => ['date' => '2026-01-06 00:00:00']],
                    ['id' => 3, 'amount' => 4.0, 'employee' => ['id' => 43], 'offerprojectbase' => ['id' => 101], 'offerprojectline' => ['id' => 11], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-07 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 2]],
                    ['id' => 11, 'invoicebasis' => ['id' => 4]],
                ],
            ],
        ]);

        $result = Billability::forTeam('2026-01-01', '2026-01-31', [42, 43]);

        $this->assertEquals(20.0, $result['total_hours']);
        $this->assertEquals(16.0, $result['billable_hours']);
        $this->assertEquals(4.0, $result['non_billable_hours']);
        $this->assertEquals(80.0, $result['billability_percentage']);

        $this->assertCount(2, $result['by_employee']);

        // Employee 42: 16h total, 16h billable = 100%
        $emp42 = $result['by_employee'][0];
        $this->assertEquals(42, $emp42['employee_id']);
        $this->assertEquals(16.0, $emp42['total_hours']);
        $this->assertEquals(16.0, $emp42['billable_hours']);
        $this->assertEquals(100.0, $emp42['billability_percentage']);

        // Employee 43: 4h total, 0h billable = 0%
        $emp43 = $result['by_employee'][1];
        $this->assertEquals(43, $emp43['employee_id']);
        $this->assertEquals(4.0, $emp43['total_hours']);
        $this->assertEquals(0.0, $emp43['billable_hours']);
        $this->assertEquals(0.0, $emp43['billability_percentage']);
    }

    public function test_for_team_without_employee_filter(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 2]],
                ],
            ],
        ]);

        $result = Billability::forTeam('2026-01-01', '2026-01-31');

        $this->assertEquals(8.0, $result['total_hours']);
        $this->assertEquals(8.0, $result['billable_hours']);
        $this->assertCount(1, $result['by_employee']);
    }

    public function test_for_team_with_zero_hours(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'rows' => []],
        ]);

        $result = Billability::forTeam('2026-01-01', '2026-01-31');

        $this->assertEquals(0.0, $result['total_hours']);
        $this->assertEquals(0.0, $result['billability_percentage']);
        $this->assertEmpty($result['by_employee']);
    }

    // --- forProject ---

    public function test_for_project_calculates_utilization(): void
    {
        $this->mockTransport([
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 1, 'description' => 'Development', 'invoicebasis' => ['id' => 2], 'amount' => 100.0, 'amountwritten' => 80.0],
                    ['id' => 2, 'description' => 'Design', 'invoicebasis' => ['id' => 1], 'amount' => 50.0, 'amountwritten' => 50.0],
                    ['id' => 3, 'description' => 'Meetings', 'invoicebasis' => ['id' => 4], 'amount' => 20.0, 'amountwritten' => 15.0],
                ],
            ],
        ]);

        $result = Billability::forProject(99);

        $this->assertEquals(99, $result['project_id']);
        $this->assertEquals(170.0, $result['total_budgeted']);
        $this->assertEquals(145.0, $result['total_actual']);
        $this->assertEquals(25.0, $result['total_remaining']);
        $this->assertEquals(85.3, $result['utilization_percentage']);

        $this->assertCount(3, $result['lines']);

        $this->assertEquals(1, $result['lines'][0]['offerprojectline_id']);
        $this->assertEquals('Development', $result['lines'][0]['description']);
        $this->assertEquals('COSTING', $result['lines'][0]['invoicebasis']);
        $this->assertEquals('FIXED', $result['lines'][1]['invoicebasis']);
        $this->assertEquals('NONBILLABLE', $result['lines'][2]['invoicebasis']);
        $this->assertEquals(100.0, $result['lines'][0]['budgeted_hours']);
        $this->assertEquals(80.0, $result['lines'][0]['actual_hours']);
        $this->assertEquals(20.0, $result['lines'][0]['remaining_hours']);
        $this->assertEquals(80.0, $result['lines'][0]['utilization_percentage']);
    }

    public function test_for_project_with_zero_budget(): void
    {
        $this->mockTransport([
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 1, 'description' => 'Ad hoc', 'invoicebasis' => ['id' => 2], 'amount' => 0.0, 'amountwritten' => 10.0],
                ],
            ],
        ]);

        $result = Billability::forProject(99);

        $this->assertEquals(0.0, $result['utilization_percentage']);
        $this->assertEquals(0.0, $result['lines'][0]['utilization_percentage']);
    }

    public function test_for_project_with_no_lines(): void
    {
        $this->mockTransport([
            ['method' => 'offerprojectline.get', 'rows' => []],
        ]);

        $result = Billability::forProject(99);

        $this->assertEquals(99, $result['project_id']);
        $this->assertEquals(0.0, $result['total_budgeted']);
        $this->assertEquals(0.0, $result['total_actual']);
        $this->assertEquals(0.0, $result['utilization_percentage']);
        $this->assertEmpty($result['lines']);
    }

    // --- uninvoicedHours ---

    public function test_uninvoiced_hours_returns_matching_hours(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'date' => ['date' => '2026-01-05 00:00:00'], 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'description' => 'Development work'],
                    ['id' => 2, 'date' => ['date' => '2026-01-06 00:00:00'], 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 101], 'description' => 'Code review'],
                ],
            ],
        ]);

        $result = Billability::uninvoicedHours('2026-01-01', '2026-01-31', 42);

        $this->assertEquals('2026-01-01', $result['from']);
        $this->assertEquals('2026-01-31', $result['to']);
        $this->assertEquals(12.0, $result['total_hours']);
        $this->assertCount(2, $result['hours']);
        $this->assertEquals(1, $result['hours'][0]['id']);
        $this->assertEquals('2026-01-05', $result['hours'][0]['date']);
        $this->assertEquals('Development work', $result['hours'][0]['description']);
        $this->assertEquals(42, $result['hours'][0]['employee']);
    }

    public function test_uninvoiced_hours_without_employee_filter(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'date' => ['date' => '2026-01-05 00:00:00'], 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'description' => 'Work'],
                ],
            ],
        ]);

        $result = Billability::uninvoicedHours('2026-01-01', '2026-01-31');

        $this->assertEquals(8.0, $result['total_hours']);
        $this->assertCount(1, $result['hours']);
    }

    public function test_uninvoiced_hours_with_no_results(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'rows' => []],
        ]);

        $result = Billability::uninvoicedHours('2026-01-01', '2026-01-31');

        $this->assertEquals(0.0, $result['total_hours']);
        $this->assertEmpty($result['hours']);
    }

    // --- invoiceability: Nacalculatie and Begroot ---

    public function test_invoiceability_counts_costing_and_budgeted_hours_linked_to_an_invoice_line(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'rows' => [
                // Nacalculatie: 10 hours written, 3 of them on an invoice line.
                self::hourRow(1, 3.0, 42, 10, invoiceLine: 500),
                self::hourRow(2, 7.0, 42, 10),
                // Begroot: 5 hours written, 4 of them invoiced.
                self::hourRow(3, 4.0, 42, 11, invoiceLine: 501),
                self::hourRow(4, 1.0, 42, 11),
                // Not billable, so no part of invoiceability.
                self::hourRow(5, 8.0, 42, 12),
            ]],
            ['method' => 'offerprojectline.get', 'rows' => [
                ['id' => 10, 'invoicebasis' => ['id' => 2]],
                ['id' => 11, 'invoicebasis' => ['id' => 3]],
                ['id' => 12, 'invoicebasis' => ['id' => 4]],
            ]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(42, $result['employee_id']);
        $this->assertEquals(23.0, $result['total_hours']);
        $this->assertEquals(15.0, $result['billable_hours']);
        $this->assertEquals(7.0, $result['invoiced_hours']);
        $this->assertEquals(8.0, $result['uninvoiced_hours']);
        $this->assertEquals(15.0, $result['exact_hours']);
        $this->assertEquals(0.0, $result['estimated_hours']);
        $this->assertEquals(0.0, $result['unmeasurable_hours']);
        $this->assertEquals(46.7, $result['invoiceability_percentage']);
        $this->assertEquals(['billable_hours' => 10.0, 'invoiced_hours' => 3.0], $result['by_invoice_basis']['COSTING']);
        $this->assertEquals(['billable_hours' => 5.0, 'invoiced_hours' => 4.0], $result['by_invoice_basis']['BUDGETED']);
        $this->assertEquals(['billable_hours' => 0.0, 'invoiced_hours' => 0.0], $result['by_invoice_basis']['FIXED']);
        // Without Fixed lines there is nothing to look up per line.
        $this->assertNotContains('invoiceline.get', $this->calledMethods());
    }

    // --- invoiceability: Fixed ---

    public function test_invoiceability_fixed_line_counts_what_was_invoiced_without_a_cap(): void
    {
        // Sold and invoiced 35 hours, worked 10, all by one person.
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => [self::hourRow(1, 10.0, 42, 20)]],
            ['method' => 'hour.get', 'rows' => [self::hourRow(1, 10.0, 42, 20)]],
            ['method' => 'offerprojectline.get', 'rows' => [['id' => 20, 'invoicebasis' => ['id' => 1]]]],
            ['method' => 'invoiceline.get', 'rows' => [self::invoiceLineRow(700, 20, 35.0)]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(10.0, $result['billable_hours']);
        $this->assertEquals(35.0, $result['invoiced_hours']);
        $this->assertEquals(0.0, $result['uninvoiced_hours']);
        $this->assertEquals(10.0, $result['exact_hours'], 'one person on the line: the invoiced hours are theirs');
        $this->assertEquals(350.0, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_shared_fixed_line_is_split_pro_rata_over_its_whole_life(): void
    {
        // Line 30 was invoiced for 40 hours. Over its life employee 42 wrote 30 hours and
        // employee 43 wrote 20, so each hour on it counts for 0.8 invoiced hours.
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => [
                self::hourRow(1, 10.0, 42, 30),
                self::hourRow(2, 20.0, 42, 30),
                self::hourRow(3, 20.0, 43, 30),
            ]],
            // The requested period holds only 10 of employee 42's hours.
            ['method' => 'hour.get', 'rows' => [self::hourRow(1, 10.0, 42, 30)]],
            ['method' => 'offerprojectline.get', 'rows' => [['id' => 30, 'invoicebasis' => ['id' => 1]]]],
            ['method' => 'invoiceline.get', 'rows' => [self::invoiceLineRow(701, 30, 40.0)]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(10.0, $result['billable_hours']);
        $this->assertEquals(8.0, $result['invoiced_hours']);
        $this->assertEquals(2.0, $result['uninvoiced_hours']);
        $this->assertEquals(10.0, $result['estimated_hours']);
        $this->assertEquals(0.0, $result['exact_hours']);
        $this->assertEquals(80.0, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_credit_lines_reduce_the_invoiced_hours(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => [self::hourRow(1, 60.0, 42, 20)]],
            ['method' => 'hour.get', 'rows' => [self::hourRow(1, 60.0, 42, 20)]],
            ['method' => 'offerprojectline.get', 'rows' => [['id' => 20, 'invoicebasis' => ['id' => 1]]]],
            ['method' => 'invoiceline.get', 'rows' => [
                self::invoiceLineRow(700, 20, 40.0),
                self::invoiceLineRow(701, 20, -10.0),
            ]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(30.0, $result['invoiced_hours']);
        $this->assertEquals(50.0, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_fixed_line_never_invoiced_is_exactly_zero(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => [
                self::hourRow(1, 8.0, 42, 20),
                self::hourRow(2, 4.0, 43, 20),
            ]],
            ['method' => 'hour.get', 'rows' => [self::hourRow(1, 8.0, 42, 20)]],
            ['method' => 'offerprojectline.get', 'rows' => [['id' => 20, 'invoicebasis' => ['id' => 1]]]],
            ['method' => 'invoiceline.get', 'rows' => []],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(0.0, $result['invoiced_hours']);
        $this->assertEquals(8.0, $result['uninvoiced_hours']);
        $this->assertEquals(8.0, $result['exact_hours'], 'nothing invoiced is certain, even on a shared line');
        $this->assertEquals(0.0, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_fixed_line_invoiced_in_other_units_is_unmeasurable(): void
    {
        // Line 40 is Fixed and invoiced as one piece; line 10 is Nacalculatie with 2 hours invoiced.
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => [self::hourRow(1, 8.0, 42, 40)]],
            ['method' => 'hour.get', 'rows' => [
                self::hourRow(1, 8.0, 42, 40),
                self::hourRow(2, 2.0, 42, 10, invoiceLine: 500),
            ]],
            ['method' => 'offerprojectline.get', 'rows' => [
                ['id' => 40, 'invoicebasis' => ['id' => 1]],
                ['id' => 10, 'invoicebasis' => ['id' => 2]],
            ]],
            ['method' => 'invoiceline.get', 'rows' => [
                self::invoiceLineRow(702, 40, 1.0, unit: 3, price: 5000.0),
                // A text line without a unit or a price is not invoicing.
                ['id' => 703, 'part' => ['id' => 40], 'amount' => 1.0, 'unit' => null, 'sellingprice' => 0.0],
            ]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(10.0, $result['billable_hours']);
        $this->assertEquals(8.0, $result['unmeasurable_hours']);
        $this->assertEquals(2.0, $result['invoiced_hours']);
        $this->assertEquals(0.0, $result['uninvoiced_hours']);
        // 2 invoiced of the 2 measurable hours; the piece-priced line is left out.
        $this->assertEquals(100.0, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_groups_by_project(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => [self::hourRow(1, 8.0, 42, 20, project: 99)]],
            ['method' => 'hour.get', 'rows' => [
                self::hourRow(1, 8.0, 42, 20, project: 99),
                self::hourRow(2, 4.0, 42, 10, project: 101),
            ]],
            ['method' => 'offerprojectline.get', 'rows' => [
                ['id' => 20, 'invoicebasis' => ['id' => 1]],
                ['id' => 10, 'invoicebasis' => ['id' => 2]],
            ]],
            ['method' => 'invoiceline.get', 'rows' => [self::invoiceLineRow(700, 20, 8.0)]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertCount(2, $result['by_project']);
        $this->assertEquals(['offerprojectbase_id' => 99, 'billable_hours' => 8.0, 'invoiced_hours' => 8.0, 'uninvoiced_hours' => 0.0], $result['by_project'][0]);
        $this->assertEquals(['offerprojectbase_id' => 101, 'billable_hours' => 4.0, 'invoiced_hours' => 0.0, 'uninvoiced_hours' => 4.0], $result['by_project'][1]);
    }

    public function test_invoiceability_looks_up_fixed_lines_in_batches(): void
    {
        $hours = [];
        $lines = [];
        foreach (range(1, 150) as $i) {
            $hours[] = self::hourRow($i, 1.0, 42, 1000 + $i);
            $lines[] = ['id' => 1000 + $i, 'invoicebasis' => ['id' => 1]];
        }
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => $hours],
            ['method' => 'hour.get', 'rows' => $hours],
            ['method' => 'offerprojectline.get', 'rows' => $lines],
        ]);

        Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $invoiceLineCalls = array_values(array_filter($this->calls, fn (array $call) => $call['method'] === 'invoiceline.get'));
        $this->assertCount(2, $invoiceLineCalls);
        $this->assertCount(100, $invoiceLineCalls[0]['filters'][0]['value']);
        $this->assertCount(50, $invoiceLineCalls[1]['filters'][0]['value']);
    }

    public function test_invoiceability_for_employee_with_zero_billable_hours(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'rows' => [self::hourRow(1, 8.0, 42, 10)]],
            ['method' => 'offerprojectline.get', 'rows' => [['id' => 10, 'invoicebasis' => ['id' => 4]]]],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(8.0, $result['total_hours']);
        $this->assertEquals(0.0, $result['billable_hours']);
        $this->assertEquals(0.0, $result['invoiceability_percentage']);
    }

    // --- invoiceabilityForTeam ---

    public function test_invoiceability_for_team_splits_a_shared_fixed_line_between_employees(): void
    {
        $lineHours = [self::hourRow(1, 30.0, 42, 30), self::hourRow(2, 20.0, 43, 30)];
        $this->mockTransport([
            ['method' => 'hour.get', 'filter' => 'hour.offerprojectline', 'rows' => $lineHours],
            ['method' => 'hour.get', 'rows' => [...$lineHours, self::hourRow(3, 5.0, 43, 10, invoiceLine: 500)]],
            ['method' => 'offerprojectline.get', 'rows' => [
                ['id' => 30, 'invoicebasis' => ['id' => 1]],
                ['id' => 10, 'invoicebasis' => ['id' => 2]],
            ]],
            ['method' => 'invoiceline.get', 'rows' => [self::invoiceLineRow(701, 30, 40.0)]],
        ]);

        $result = Billability::invoiceabilityForTeam('2026-01-01', '2026-01-31', [42, 43]);

        // The 40 Fixed hours split 24 / 16, plus 5 invoiced Nacalculatie hours of employee 43.
        $this->assertEquals(55.0, $result['billable_hours']);
        $this->assertEquals(45.0, $result['invoiced_hours']);
        $this->assertEquals(50.0, $result['estimated_hours']);
        $this->assertEquals(5.0, $result['exact_hours']);
        $this->assertEquals(81.8, $result['invoiceability_percentage']);

        $this->assertCount(2, $result['by_employee']);
        [$first, $second] = $result['by_employee'];
        $this->assertEquals(42, $first['employee_id']);
        $this->assertEquals(30.0, $first['billable_hours']);
        $this->assertEquals(24.0, $first['invoiced_hours']);
        $this->assertEquals(80.0, $first['invoiceability_percentage']);
        $this->assertEquals(43, $second['employee_id']);
        $this->assertEquals(21.0, $second['invoiced_hours']);
        $this->assertEquals(84.0, $second['invoiceability_percentage']);
    }

    public function test_invoiceability_for_team_with_zero_hours(): void
    {
        $this->mockTransport([
            ['method' => 'hour.get', 'rows' => []],
        ]);

        $result = Billability::invoiceabilityForTeam('2026-01-01', '2026-01-31');

        $this->assertEquals(0.0, $result['billable_hours']);
        $this->assertEquals(0.0, $result['invoiceability_percentage']);
        $this->assertEmpty($result['by_employee']);
    }

    // --- Invoice basis classification ---

    public function test_all_billable_invoice_bases(): void
    {
        // IDs 1=Fixed, 2=Costing, 3=Budgeted are all billable
        $billableIds = [1, 2, 3];

        foreach ($billableIds as $basisId) {
            $this->mockTransport([
                [
                    'method' => 'hour.get',
                    'rows' => [
                        ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    ],
                ],
                [
                    'method' => 'offerprojectline.get',
                    'rows' => [
                        ['id' => 10, 'invoicebasis' => ['id' => $basisId]],
                    ],
                ],
            ]);

            $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

            $this->assertEquals(8.0, $result['billable_hours'], "Invoice basis ID {$basisId} should be billable");
            $this->assertEquals(0.0, $result['non_billable_hours'], "Invoice basis ID {$basisId} should not be non-billable");
        }
    }

    public function test_nonbillable_invoice_basis(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 4]],
                ],
            ],
        ]);

        $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(0.0, $result['billable_hours']);
        $this->assertEquals(8.0, $result['non_billable_hours']);
    }

    public function test_percentage_rounds_to_one_decimal(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 1.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 1], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    ['id' => 2, 'amount' => 2.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => null, 'status' => ['id' => 1], 'invoiceline' => null, 'date' => ['date' => '2026-01-06 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 2]],
                ],
            ],
        ]);

        $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');

        // 1/3 = 33.333...% rounds to 33.3
        $this->assertEquals(33.3, $result['billability_percentage']);
    }
}
