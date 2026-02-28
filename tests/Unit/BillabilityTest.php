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

    private function mockTransport(array $callMap): void
    {
        $mock = $this->createMock(JsonRpcClient::class);
        $mock->method('paginate')
            ->willReturnCallback(function (string $method, array $params) use ($callMap) {
                foreach ($callMap as $entry) {
                    if ($entry['method'] === $method) {
                        return new JsonRpcResponse([
                            'id' => 1,
                            'result' => [
                                'rows' => $entry['rows'],
                                'count' => count($entry['rows']),
                                'more_items_in_collection' => false,
                            ],
                        ]);
                    }
                }

                return new JsonRpcResponse([
                    'id' => 1,
                    'result' => ['rows' => [], 'count' => 0, 'more_items_in_collection' => false],
                ]);
            });

        GrippClient::setTransport($mock);
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

    // --- invoiceabilityForEmployee ---

    public function test_invoiceability_for_employee_calculates_correctly(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    // Billable + invoiced
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 3], 'invoiceline' => ['id' => 5], 'date' => ['date' => '2026-01-05 00:00:00']],
                    // Billable + NOT invoiced
                    ['id' => 2, 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-06 00:00:00']],
                    // Non-billable (should be excluded from invoiceability)
                    ['id' => 3, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 101], 'offerprojectline' => ['id' => 11], 'status' => ['id' => 3], 'invoiceline' => null, 'date' => ['date' => '2026-01-07 00:00:00']],
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

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(42, $result['employee_id']);
        $this->assertEquals(20.0, $result['total_hours']);
        $this->assertEquals(12.0, $result['billable_hours']);
        $this->assertEquals(8.0, $result['invoiced_hours']);
        $this->assertEquals(4.0, $result['uninvoiced_hours']);
        // 8 invoiced / 20 total = 40%
        $this->assertEquals(40.0, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_for_employee_groups_by_project(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    // FIXED line (invoiced at line level via InvoiceLine.part)
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 3], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    // COSTING line (not invoiced - no invoiceline on hour)
                    ['id' => 2, 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 101], 'offerprojectline' => ['id' => 12], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-06 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 1]], // FIXED
                    ['id' => 12, 'invoicebasis' => ['id' => 2]], // COSTING
                ],
            ],
            [
                // InvoiceLine.part shows line 10 (FIXED) has been invoiced
                'method' => 'invoiceline.get',
                'rows' => [
                    ['id' => 100, 'part' => ['id' => 10]],
                ],
            ],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertCount(2, $result['by_project']);
        // Project 99: FIXED line invoiced via InvoiceLine.part
        $this->assertEquals(99, $result['by_project'][0]['offerprojectbase_id']);
        $this->assertEquals(8.0, $result['by_project'][0]['invoiced_hours']);
        $this->assertEquals(0.0, $result['by_project'][0]['uninvoiced_hours']);
        // Project 101: COSTING line not invoiced (no hour.invoiceline)
        $this->assertEquals(101, $result['by_project'][1]['offerprojectbase_id']);
        $this->assertEquals(0.0, $result['by_project'][1]['invoiced_hours']);
        $this->assertEquals(4.0, $result['by_project'][1]['uninvoiced_hours']);
    }

    public function test_invoiceability_fixed_and_budgeted_use_line_level_invoicing(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    // FIXED line - hour has no invoiceline (normal for FIXED)
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 3], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                    // BUDGETED line - hour has no invoiceline (normal for BUDGETED)
                    ['id' => 2, 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 11], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-06 00:00:00']],
                    // COSTING line - hour HAS invoiceline (hour-level billing)
                    ['id' => 3, 'amount' => 2.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 12], 'status' => ['id' => 3], 'invoiceline' => ['id' => 50], 'date' => ['date' => '2026-01-07 00:00:00']],
                    // COSTING line - NOT invoiced
                    ['id' => 4, 'amount' => 2.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 12], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-08 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 1]], // FIXED
                    ['id' => 11, 'invoicebasis' => ['id' => 3]], // BUDGETED
                    ['id' => 12, 'invoicebasis' => ['id' => 2]], // COSTING
                ],
            ],
            [
                // Only FIXED line 10 has been invoiced; BUDGETED line 11 has not
                'method' => 'invoiceline.get',
                'rows' => [
                    ['id' => 200, 'part' => ['id' => 10]],
                ],
            ],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        // All 16h are billable (FIXED 8 + BUDGETED 4 + COSTING 4)
        $this->assertEquals(16.0, $result['billable_hours']);
        // Invoiced: FIXED 8h (line-level) + COSTING 2h (hour-level) = 10h
        $this->assertEquals(10.0, $result['invoiced_hours']);
        // Uninvoiced: BUDGETED 4h (line not invoiced) + COSTING 2h (no hour.invoiceline) = 6h
        $this->assertEquals(6.0, $result['uninvoiced_hours']);
        // 10 invoiced / 16 total = 62.5%
        $this->assertEquals(62.5, $result['invoiceability_percentage']);
    }

    public function test_invoiceability_for_employee_with_zero_billable_hours(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 1], 'invoiceline' => null, 'date' => ['date' => '2026-01-05 00:00:00']],
                ],
            ],
            [
                'method' => 'offerprojectline.get',
                'rows' => [
                    ['id' => 10, 'invoicebasis' => ['id' => 4]],
                ],
            ],
        ]);

        $result = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');

        $this->assertEquals(8.0, $result['total_hours']);
        $this->assertEquals(0.0, $result['billable_hours']);
        $this->assertEquals(0.0, $result['invoiceability_percentage']);
    }

    // --- invoiceabilityForTeam ---

    public function test_invoiceability_for_team_aggregates_employees(): void
    {
        $this->mockTransport([
            [
                'method' => 'hour.get',
                'rows' => [
                    // Employee 42: billable + invoiced
                    ['id' => 1, 'amount' => 8.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 3], 'invoiceline' => ['id' => 5], 'date' => ['date' => '2026-01-05 00:00:00']],
                    // Employee 42: billable + NOT invoiced
                    ['id' => 2, 'amount' => 4.0, 'employee' => ['id' => 42], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 2], 'invoiceline' => null, 'date' => ['date' => '2026-01-06 00:00:00']],
                    // Employee 43: billable + invoiced
                    ['id' => 3, 'amount' => 6.0, 'employee' => ['id' => 43], 'offerprojectbase' => ['id' => 99], 'offerprojectline' => ['id' => 10], 'status' => ['id' => 3], 'invoiceline' => ['id' => 6], 'date' => ['date' => '2026-01-07 00:00:00']],
                    // Employee 43: non-billable (excluded)
                    ['id' => 4, 'amount' => 2.0, 'employee' => ['id' => 43], 'offerprojectbase' => ['id' => 101], 'offerprojectline' => ['id' => 11], 'status' => ['id' => 3], 'invoiceline' => null, 'date' => ['date' => '2026-01-08 00:00:00']],
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

        $result = Billability::invoiceabilityForTeam('2026-01-01', '2026-01-31', [42, 43]);

        $this->assertEquals(20.0, $result['total_hours']);
        $this->assertEquals(18.0, $result['billable_hours']);
        $this->assertEquals(14.0, $result['invoiced_hours']);
        $this->assertEquals(4.0, $result['uninvoiced_hours']);
        // 14 invoiced / 20 total = 70%
        $this->assertEquals(70.0, $result['invoiceability_percentage']);

        $this->assertCount(2, $result['by_employee']);

        // Employee 42: 8 invoiced / 12 total = 66.7%
        $emp42 = $result['by_employee'][0];
        $this->assertEquals(42, $emp42['employee_id']);
        $this->assertEquals(12.0, $emp42['total_hours']);
        $this->assertEquals(12.0, $emp42['billable_hours']);
        $this->assertEquals(8.0, $emp42['invoiced_hours']);
        $this->assertEquals(66.7, $emp42['invoiceability_percentage']);

        // Employee 43: 6 invoiced / 8 total = 75%
        $emp43 = $result['by_employee'][1];
        $this->assertEquals(43, $emp43['employee_id']);
        $this->assertEquals(8.0, $emp43['total_hours']);
        $this->assertEquals(6.0, $emp43['billable_hours']);
        $this->assertEquals(6.0, $emp43['invoiced_hours']);
        $this->assertEquals(75.0, $emp43['invoiceability_percentage']);
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
