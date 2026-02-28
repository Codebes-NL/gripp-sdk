<?php

namespace CodeBes\GrippSdk\Features;

use CodeBes\GrippSdk\GrippClient;
use Illuminate\Support\Collection;

/**
 * Billability (declarabiliteit) calculations.
 *
 * Computed feature that aggregates data from Hour and OfferProjectLine resources
 * to calculate billable vs non-billable time for employees, teams, and projects.
 *
 * An hour is billable when its linked OfferProjectLine has invoicebasis != 'NONBILLABLE'.
 * An hour is uninvoiced when status is DEFINITIVE or AUTHORIZED and invoiceline is null.
 *
 * @example
 * // Employee billability for January
 * $result = Billability::forEmployee(42, '2026-01-01', '2026-01-31');
 * // $result['billability_percentage'] => 80.0
 *
 * // Team overview
 * $team = Billability::forTeam('2026-01-01', '2026-01-31', [42, 43, 44]);
 *
 * // Project utilization
 * $project = Billability::forProject(99);
 *
 * // Find uninvoiced hours
 * $uninvoiced = Billability::uninvoicedHours('2026-01-01', '2026-01-31');
 *
 * // Invoiceability (facturabiliteit) - what % of billable hours are actually invoiced
 * $inv = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');
 * // $inv['invoiceability_percentage'] => 45.4
 *
 * $teamInv = Billability::invoiceabilityForTeam('2026-01-01', '2026-01-31');
 */
class Billability
{
    /**
     * Status IDs in the Gripp API.
     * 1 = Concept, 2 = Definitief (Definitive), 3 = Gefiatteerd (Authorized).
     */
    private const STATUS_DEFINITIVE = 2;

    private const STATUS_AUTHORIZED = 3;

    /**
     * Invoice basis IDs in the Gripp API.
     * 1 = Fixed, 2 = Nacalculatie (Costing), 3 = Begroot (Budgeted), 4 = Niet doorbelasten (Non-billable).
     */
    private const INVOICEBASIS_COSTING = 2;

    private const INVOICEBASIS_NONBILLABLE = 4;

    private const INVOICEBASIS_LABELS = [
        1 => 'FIXED',
        2 => 'COSTING',
        3 => 'BUDGETED',
        4 => 'NONBILLABLE',
    ];

    /**
     * Calculate billability for a single employee within a date range.
     *
     * @return array{employee_id: int, from: string, to: string, total_hours: float, billable_hours: float, non_billable_hours: float, billability_percentage: float, uninvoiced_hours: float, by_project: array}
     */
    public static function forEmployee(int $employeeId, string $from, string $to): array
    {
        $hours = self::paginatedQuery('hour', [
            ['field' => 'hour.employee', 'operator' => 'equals', 'value' => $employeeId],
            ['field' => 'hour.date', 'operator' => 'between', 'value' => $from, 'value2' => $to],
        ]);

        $lineLookup = self::resolveLineInvoiceBasis($hours);

        $totalHours = 0.0;
        $billableHours = 0.0;
        $nonBillableHours = 0.0;
        $uninvoicedHours = 0.0;
        $byProject = [];

        foreach ($hours as $hour) {
            $amount = (float) ($hour['amount'] ?? 0);
            $totalHours += $amount;

            $lineId = self::resolveId($hour['offerprojectline'] ?? null);
            $invoiceBasisId = $lineId ? ($lineLookup[$lineId] ?? null) : null;
            $isBillable = $invoiceBasisId !== null && $invoiceBasisId !== self::INVOICEBASIS_NONBILLABLE;

            if ($isBillable) {
                $billableHours += $amount;
            } else {
                $nonBillableHours += $amount;
            }

            $statusId = self::resolveId($hour['status'] ?? null);
            $invoiceLine = $hour['invoiceline'] ?? null;
            if (in_array($statusId, [self::STATUS_DEFINITIVE, self::STATUS_AUTHORIZED]) && $invoiceLine === null) {
                $uninvoicedHours += $amount;
            }

            $projectId = self::resolveId($hour['offerprojectbase'] ?? null);
            if ($projectId !== null) {
                if (! isset($byProject[$projectId])) {
                    $byProject[$projectId] = [
                        'offerprojectbase_id' => $projectId,
                        'total_hours' => 0.0,
                        'billable_hours' => 0.0,
                    ];
                }
                $byProject[$projectId]['total_hours'] += $amount;
                if ($isBillable) {
                    $byProject[$projectId]['billable_hours'] += $amount;
                }
            }
        }

        return [
            'employee_id' => $employeeId,
            'from' => $from,
            'to' => $to,
            'total_hours' => $totalHours,
            'billable_hours' => $billableHours,
            'non_billable_hours' => $nonBillableHours,
            'billability_percentage' => $totalHours > 0
                ? round(($billableHours / $totalHours) * 100, 1)
                : 0.0,
            'uninvoiced_hours' => $uninvoicedHours,
            'by_project' => array_values($byProject),
        ];
    }

    /**
     * Calculate billability for a team (multiple employees) within a date range.
     *
     * @param  string     $from        Start date (Y-m-d)
     * @param  string     $to          End date (Y-m-d)
     * @param  int[]|null $employeeIds Employee IDs to include, or null for all
     * @return array{from: string, to: string, total_hours: float, billable_hours: float, non_billable_hours: float, billability_percentage: float, by_employee: array}
     */
    public static function forTeam(string $from, string $to, ?array $employeeIds = null): array
    {
        $filters = [
            ['field' => 'hour.date', 'operator' => 'between', 'value' => $from, 'value2' => $to],
        ];

        if ($employeeIds !== null) {
            $filters[] = ['field' => 'hour.employee', 'operator' => 'in', 'value' => $employeeIds];
        }

        $hours = self::paginatedQuery('hour', $filters);
        $lineLookup = self::resolveLineInvoiceBasis($hours);

        $totalHours = 0.0;
        $billableHours = 0.0;
        $nonBillableHours = 0.0;
        $byEmployee = [];

        foreach ($hours as $hour) {
            $amount = (float) ($hour['amount'] ?? 0);
            $totalHours += $amount;

            $lineId = self::resolveId($hour['offerprojectline'] ?? null);
            $invoiceBasisId = $lineId ? ($lineLookup[$lineId] ?? null) : null;
            $isBillable = $invoiceBasisId !== null && $invoiceBasisId !== self::INVOICEBASIS_NONBILLABLE;

            if ($isBillable) {
                $billableHours += $amount;
            } else {
                $nonBillableHours += $amount;
            }

            $empId = self::resolveId($hour['employee'] ?? null);
            if ($empId !== null) {
                if (! isset($byEmployee[$empId])) {
                    $byEmployee[$empId] = [
                        'employee_id' => $empId,
                        'total_hours' => 0.0,
                        'billable_hours' => 0.0,
                        'billability_percentage' => 0.0,
                    ];
                }
                $byEmployee[$empId]['total_hours'] += $amount;
                if ($isBillable) {
                    $byEmployee[$empId]['billable_hours'] += $amount;
                }
            }
        }

        foreach ($byEmployee as &$emp) {
            $emp['billability_percentage'] = $emp['total_hours'] > 0
                ? round(($emp['billable_hours'] / $emp['total_hours']) * 100, 1)
                : 0.0;
        }
        unset($emp);

        return [
            'from' => $from,
            'to' => $to,
            'total_hours' => $totalHours,
            'billable_hours' => $billableHours,
            'non_billable_hours' => $nonBillableHours,
            'billability_percentage' => $totalHours > 0
                ? round(($billableHours / $totalHours) * 100, 1)
                : 0.0,
            'by_employee' => array_values($byEmployee),
        ];
    }

    /**
     * Calculate project utilization from its offer/project lines.
     *
     * @return array{project_id: int, total_budgeted: float, total_actual: float, total_remaining: float, utilization_percentage: float, lines: array}
     */
    public static function forProject(int $projectId): array
    {
        $lines = self::paginatedQuery('offerprojectline', [
            ['field' => 'offerprojectline.offerprojectbase', 'operator' => 'equals', 'value' => $projectId],
        ]);

        $totalBudgeted = 0.0;
        $totalActual = 0.0;
        $lineResults = [];

        foreach ($lines as $line) {
            $budgeted = (float) ($line['amount'] ?? 0);
            $actual = (float) ($line['amountwritten'] ?? 0);
            $remaining = $budgeted - $actual;

            $totalBudgeted += $budgeted;
            $totalActual += $actual;

            $basisId = self::resolveInvoiceBasisId($line['invoicebasis'] ?? null);

            $lineResults[] = [
                'offerprojectline_id' => $line['id'],
                'description' => $line['description'] ?? '',
                'invoicebasis' => self::INVOICEBASIS_LABELS[$basisId] ?? 'UNKNOWN',
                'budgeted_hours' => $budgeted,
                'actual_hours' => $actual,
                'remaining_hours' => $remaining,
                'utilization_percentage' => $budgeted > 0
                    ? round(($actual / $budgeted) * 100, 1)
                    : 0.0,
            ];
        }

        $totalRemaining = $totalBudgeted - $totalActual;

        return [
            'project_id' => $projectId,
            'total_budgeted' => $totalBudgeted,
            'total_actual' => $totalActual,
            'total_remaining' => $totalRemaining,
            'utilization_percentage' => $totalBudgeted > 0
                ? round(($totalActual / $totalBudgeted) * 100, 1)
                : 0.0,
            'lines' => $lineResults,
        ];
    }

    /**
     * Calculate invoiceability (facturabiliteit) for a single employee.
     *
     * Measures what percentage of billable hours have actually been invoiced.
     * Handles all invoice basis types correctly:
     * - COSTING: checks hour.invoiceline (hours are individually linked to invoices)
     * - FIXED/BUDGETED: checks if the offerprojectline has been invoiced via InvoiceLine.part
     *
     * @return array{employee_id: int, from: string, to: string, total_hours: float, billable_hours: float, invoiced_hours: float, uninvoiced_hours: float, invoiceability_percentage: float, by_project: array}
     */
    public static function invoiceabilityForEmployee(int $employeeId, string $from, string $to): array
    {
        $hours = self::paginatedQuery('hour', [
            ['field' => 'hour.employee', 'operator' => 'equals', 'value' => $employeeId],
            ['field' => 'hour.date', 'operator' => 'between', 'value' => $from, 'value2' => $to],
        ]);

        $lineLookup = self::resolveLineInvoiceBasis($hours);
        $invoicedLines = self::resolveInvoicedLines($hours, $from, $to);

        $totalHours = 0.0;
        $billableHours = 0.0;
        $invoicedHours = 0.0;
        $uninvoicedHours = 0.0;
        $byProject = [];

        foreach ($hours as $hour) {
            $amount = (float) ($hour['amount'] ?? 0);
            $totalHours += $amount;

            $lineId = self::resolveId($hour['offerprojectline'] ?? null);
            $invoiceBasisId = $lineId ? ($lineLookup[$lineId] ?? null) : null;
            $isBillable = $invoiceBasisId !== null && $invoiceBasisId !== self::INVOICEBASIS_NONBILLABLE;

            if (! $isBillable) {
                continue;
            }

            $billableHours += $amount;
            $isInvoiced = self::isHourInvoiced($hour, $lineId, $invoiceBasisId, $invoicedLines);

            if ($isInvoiced) {
                $invoicedHours += $amount;
            } else {
                $uninvoicedHours += $amount;
            }

            $projectId = self::resolveId($hour['offerprojectbase'] ?? null);
            if ($projectId !== null) {
                if (! isset($byProject[$projectId])) {
                    $byProject[$projectId] = [
                        'offerprojectbase_id' => $projectId,
                        'billable_hours' => 0.0,
                        'invoiced_hours' => 0.0,
                        'uninvoiced_hours' => 0.0,
                    ];
                }
                $byProject[$projectId]['billable_hours'] += $amount;
                if ($isInvoiced) {
                    $byProject[$projectId]['invoiced_hours'] += $amount;
                } else {
                    $byProject[$projectId]['uninvoiced_hours'] += $amount;
                }
            }
        }

        return [
            'employee_id' => $employeeId,
            'from' => $from,
            'to' => $to,
            'total_hours' => $totalHours,
            'billable_hours' => $billableHours,
            'invoiced_hours' => $invoicedHours,
            'uninvoiced_hours' => $uninvoicedHours,
            'invoiceability_percentage' => $totalHours > 0
                ? round(($invoicedHours / $totalHours) * 100, 1)
                : 0.0,
            'by_project' => array_values($byProject),
        ];
    }

    /**
     * Calculate invoiceability (facturabiliteit) for a team.
     *
     * Measures what percentage of billable hours have actually been invoiced
     * across multiple employees. See invoiceabilityForEmployee for invoicing logic.
     *
     * @param  string     $from        Start date (Y-m-d)
     * @param  string     $to          End date (Y-m-d)
     * @param  int[]|null $employeeIds Employee IDs to include, or null for all
     * @return array{from: string, to: string, total_hours: float, billable_hours: float, invoiced_hours: float, uninvoiced_hours: float, invoiceability_percentage: float, by_employee: array}
     */
    public static function invoiceabilityForTeam(string $from, string $to, ?array $employeeIds = null): array
    {
        $filters = [
            ['field' => 'hour.date', 'operator' => 'between', 'value' => $from, 'value2' => $to],
        ];

        if ($employeeIds !== null) {
            $filters[] = ['field' => 'hour.employee', 'operator' => 'in', 'value' => $employeeIds];
        }

        $hours = self::paginatedQuery('hour', $filters);
        $lineLookup = self::resolveLineInvoiceBasis($hours);
        $invoicedLines = self::resolveInvoicedLines($hours, $from, $to);

        $totalHours = 0.0;
        $billableHours = 0.0;
        $invoicedHours = 0.0;
        $uninvoicedHours = 0.0;
        $byEmployee = [];

        foreach ($hours as $hour) {
            $amount = (float) ($hour['amount'] ?? 0);
            $totalHours += $amount;

            $empId = self::resolveId($hour['employee'] ?? null);
            if ($empId !== null && ! isset($byEmployee[$empId])) {
                $byEmployee[$empId] = [
                    'employee_id' => $empId,
                    'total_hours' => 0.0,
                    'billable_hours' => 0.0,
                    'invoiced_hours' => 0.0,
                    'uninvoiced_hours' => 0.0,
                    'invoiceability_percentage' => 0.0,
                ];
            }
            if ($empId !== null) {
                $byEmployee[$empId]['total_hours'] += $amount;
            }

            $lineId = self::resolveId($hour['offerprojectline'] ?? null);
            $invoiceBasisId = $lineId ? ($lineLookup[$lineId] ?? null) : null;
            $isBillable = $invoiceBasisId !== null && $invoiceBasisId !== self::INVOICEBASIS_NONBILLABLE;

            if (! $isBillable) {
                continue;
            }

            $billableHours += $amount;
            $isInvoiced = self::isHourInvoiced($hour, $lineId, $invoiceBasisId, $invoicedLines);

            if ($isInvoiced) {
                $invoicedHours += $amount;
            } else {
                $uninvoicedHours += $amount;
            }

            if ($empId !== null) {
                $byEmployee[$empId]['billable_hours'] += $amount;
                if ($isInvoiced) {
                    $byEmployee[$empId]['invoiced_hours'] += $amount;
                } else {
                    $byEmployee[$empId]['uninvoiced_hours'] += $amount;
                }
            }
        }

        foreach ($byEmployee as &$emp) {
            $emp['invoiceability_percentage'] = $emp['total_hours'] > 0
                ? round(($emp['invoiced_hours'] / $emp['total_hours']) * 100, 1)
                : 0.0;
        }
        unset($emp);

        return [
            'from' => $from,
            'to' => $to,
            'total_hours' => $totalHours,
            'billable_hours' => $billableHours,
            'invoiced_hours' => $invoicedHours,
            'uninvoiced_hours' => $uninvoicedHours,
            'invoiceability_percentage' => $totalHours > 0
                ? round(($invoicedHours / $totalHours) * 100, 1)
                : 0.0,
            'by_employee' => array_values($byEmployee),
        ];
    }

    /**
     * Find uninvoiced hours within a date range.
     *
     * Uninvoiced hours have status DEFINITIVE or AUTHORIZED and no linked invoice line.
     *
     * @return array{from: string, to: string, total_hours: float, hours: array}
     */
    public static function uninvoicedHours(string $from, string $to, ?int $employeeId = null): array
    {
        $filters = [
            ['field' => 'hour.date', 'operator' => 'between', 'value' => $from, 'value2' => $to],
            ['field' => 'hour.invoiceline', 'operator' => 'isnull', 'value' => true],
            ['field' => 'hour.status', 'operator' => 'in', 'value' => [self::STATUS_DEFINITIVE, self::STATUS_AUTHORIZED]],
        ];

        if ($employeeId !== null) {
            $filters[] = ['field' => 'hour.employee', 'operator' => 'equals', 'value' => $employeeId];
        }

        $hours = self::paginatedQuery('hour', $filters);

        $totalHours = 0.0;
        $hourResults = [];

        foreach ($hours as $hour) {
            $amount = (float) ($hour['amount'] ?? 0);
            $totalHours += $amount;

            $hourResults[] = [
                'id' => $hour['id'],
                'date' => self::resolveDate($hour['date'] ?? null),
                'amount' => $amount,
                'employee' => self::resolveId($hour['employee'] ?? null),
                'offerprojectbase' => self::resolveId($hour['offerprojectbase'] ?? null),
                'description' => $hour['description'] ?? '',
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'total_hours' => $totalHours,
            'hours' => $hourResults,
        ];
    }

    /**
     * Execute a paginated query against the Gripp API.
     *
     * @param  string  $entity  Entity name (e.g. 'hour', 'offerprojectline')
     * @param  array[] $filters Raw filter arrays with field, operator, value (and optional value2)
     */
    private static function paginatedQuery(string $entity, array $filters): Collection
    {
        $transport = GrippClient::getTransport();
        $response = $transport->paginate($entity . '.get', [$filters, []]);

        return $response->toCollection();
    }

    /**
     * Batch-fetch OfferProjectLine invoice basis IDs for a collection of hours.
     *
     * @return array<int, int> Map of lineId => invoicebasis ID
     */
    private static function resolveLineInvoiceBasis(Collection $hours): array
    {
        $lineIds = $hours
            ->map(fn ($h) => self::resolveId($h['offerprojectline'] ?? null))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($lineIds)) {
            return [];
        }

        $lines = self::paginatedQuery('offerprojectline', [
            ['field' => 'offerprojectline.id', 'operator' => 'in', 'value' => $lineIds],
        ]);

        $lookup = [];
        foreach ($lines as $line) {
            $lookup[$line['id']] = self::resolveInvoiceBasisId($line['invoicebasis'] ?? null);
        }

        return $lookup;
    }

    /**
     * Determine if a billable hour has been invoiced.
     *
     * - COSTING: hour is invoiced when hour.invoiceline is set (hour-level billing)
     * - FIXED/BUDGETED: hour is invoiced when its offerprojectline appears in InvoiceLine.part
     *
     * @param  array<int, bool> $invoicedLines Set of offerprojectline IDs that have been invoiced
     */
    private static function isHourInvoiced(array $hour, ?int $lineId, ?int $invoiceBasisId, array $invoicedLines): bool
    {
        if ($invoiceBasisId === self::INVOICEBASIS_COSTING) {
            return $hour['invoiceline'] !== null;
        }

        // FIXED or BUDGETED: check if the project line has been invoiced
        return $lineId !== null && isset($invoicedLines[$lineId]);
    }

    /**
     * Find which offerprojectlines have been invoiced via InvoiceLine.part.
     *
     * For FIXED and BUDGETED lines, invoicing happens at the line level (not hour level).
     * This fetches InvoiceLines created in the date range and returns the set of
     * offerprojectline IDs that have been billed.
     *
     * @return array<int, bool> Map of offerprojectline ID => true
     */
    private static function resolveInvoicedLines(Collection $hours, string $from, string $to): array
    {
        $lineIds = $hours
            ->map(fn ($h) => self::resolveId($h['offerprojectline'] ?? null))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($lineIds)) {
            return [];
        }

        $invoiceLines = self::paginatedQuery('invoiceline', [
            ['field' => 'invoiceline.part', 'operator' => 'in', 'value' => $lineIds],
        ]);

        $invoiced = [];
        foreach ($invoiceLines as $il) {
            $partId = self::resolveId($il['part'] ?? null);
            if ($partId !== null) {
                $invoiced[$partId] = true;
            }
        }

        return $invoiced;
    }

    /**
     * Extract an ID from a value that may be a scalar or a Gripp API nested object.
     *
     * The Gripp API returns FK fields as objects: {"id": 42, "searchname": "..."}.
     * This helper handles both formats for compatibility with real API and test data.
     */
    private static function resolveId(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) && isset($value['id'])) {
            return (int) $value['id'];
        }

        if (is_int($value) || is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Extract a date string from a value that may be a string or a Gripp API date object.
     *
     * The Gripp API returns date fields as objects: {"date": "2026-01-05 00:00:00.000000", ...}.
     */
    private static function resolveDate(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value) && isset($value['date'])) {
            return substr($value['date'], 0, 10);
        }

        if (is_string($value)) {
            return substr($value, 0, 10);
        }

        return '';
    }

    /**
     * Extract invoice basis ID from a value that may be a scalar or a Gripp API object.
     *
     * Returns the numeric ID: 1=Fixed, 2=Costing, 3=Budgeted, 4=Non-billable.
     */
    private static function resolveInvoiceBasisId(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) && isset($value['id'])) {
            return (int) $value['id'];
        }

        if (is_int($value) || is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
