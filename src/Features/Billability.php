<?php

namespace CodeBes\GrippSdk\Features;

use CodeBes\GrippSdk\GrippClient;
use Illuminate\Support\Collection;

/**
 * Billability (declarabiliteit) and invoiceability (facturabiliteit) calculations.
 *
 * Computed feature that aggregates Hour, OfferProjectLine and InvoiceLine data for
 * employees, teams, and projects. The two measures answer different questions:
 *
 * - Billability: hours on paid project lines / all hours. An hour is billable when its
 *   OfferProjectLine has an invoice basis other than NONBILLABLE.
 * - Invoiceability: invoiced hours / hours on paid project lines. Ten hours on a paid
 *   line are 100% billable; if five of them are invoiced, invoiceability is 50%.
 *
 * Invoiceability is exact per hour only on COSTING (Nacalculatie) and BUDGETED (Begroot)
 * lines, where Gripp sets hour.invoiceline on every invoiced hour. On FIXED lines Gripp
 * never does: the invoice line points at the project line (invoiceline.part) with a
 * quantity, so who the invoiced hours were for is recorded nowhere. Fixed lines are
 * therefore compared per line over their whole life and split pro rata over the people
 * who wrote hours on them. See invoiceabilityForEmployee() and the README section
 * "Billability and Invoiceability".
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
 * // Find hours without an invoice line (every Fixed hour is among them)
 * $uninvoiced = Billability::uninvoicedHours('2026-01-01', '2026-01-31');
 *
 * // Invoiceability (facturabiliteit) - what share of the paid hours was invoiced
 * $inv = Billability::invoiceabilityForEmployee(42, '2026-01-01', '2026-01-31');
 * // $inv['invoiceability_percentage'] => 45.4, $inv['estimated_hours'] => 12.0
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
    private const INVOICEBASIS_FIXED = 1;

    private const INVOICEBASIS_COSTING = 2;

    private const INVOICEBASIS_BUDGETED = 3;

    private const INVOICEBASIS_NONBILLABLE = 4;

    private const INVOICEBASIS_LABELS = [
        1 => 'FIXED',
        2 => 'COSTING',
        3 => 'BUDGETED',
        4 => 'NONBILLABLE',
    ];

    /**
     * Unit ID of "uur" (hour). Only invoice lines in this unit carry an hour quantity.
     * Unit.hoursperunit is 1 for every unit, pieces and price included, so it cannot
     * convert anything else to hours.
     */
    private const UNIT_HOUR = 1;

    /** Project line IDs per `in` filter when looking up Fixed lines. */
    private const LINE_BATCH_SIZE = 100;

    private const PRECISION_EXACT = 'exact';

    private const PRECISION_ESTIMATED = 'estimated';

    private const PRECISION_UNMEASURABLE = 'unmeasurable';

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
     * Calculate invoiceability (facturabiliteit) for a single employee within a date range.
     *
     * How an hour counts as invoiced depends on the invoice basis of its project line:
     *
     * - COSTING and BUDGETED: invoiced when hour.invoiceline is set. Exact, per hour.
     * - FIXED: Gripp never sets hour.invoiceline. The hour counts for the invoiced share of
     *   its project line: every hour quantity ever invoiced on the line (invoiceline.part,
     *   unit "uur", credit lines negative) divided by every hour ever written on it, in any
     *   period. Not capped: 35 invoiced hours on 10 worked count as 35. That share is exact
     *   when one person worked on the line or it was never invoiced, and an estimate - split
     *   pro rata over the people on the line - when several people share an invoiced line.
     *   A line invoiced in another unit (pieces, price) has no hour quantity: its hours are
     *   unmeasurable and left out of the percentage.
     *
     * invoiceability_percentage = invoiced_hours / (billable_hours - unmeasurable_hours) * 100.
     * exact_hours, estimated_hours and unmeasurable_hours add up to billable_hours.
     *
     * @return array{employee_id: int, from: string, to: string, total_hours: float, billable_hours: float, invoiced_hours: float, uninvoiced_hours: float, exact_hours: float, estimated_hours: float, unmeasurable_hours: float, invoiceability_percentage: float, by_invoice_basis: array, by_project: array}
     */
    public static function invoiceabilityForEmployee(int $employeeId, string $from, string $to): array
    {
        $hours = self::paginatedQuery('hour', [
            ['field' => 'hour.employee', 'operator' => 'equals', 'value' => $employeeId],
            ['field' => 'hour.date', 'operator' => 'between', 'value' => $from, 'value2' => $to],
        ]);

        $totals = self::emptyInvoiceabilityTotals();
        $byBasis = self::emptyBasisTotals();
        $byProject = [];

        foreach (self::measureInvoicedHours($hours) as $measured) {
            self::addToTotals($totals, $measured);
            self::addToBasis($byBasis, $measured);

            $projectId = self::resolveId($measured['hour']['offerprojectbase'] ?? null);
            if (! $measured['billable'] || $projectId === null) {
                continue;
            }

            $byProject[$projectId] ??= [
                'offerprojectbase_id' => $projectId,
                'billable_hours' => 0.0,
                'invoiced_hours' => 0.0,
                'uninvoiced_hours' => 0.0,
            ];
            $byProject[$projectId]['billable_hours'] += $measured['amount'];
            $byProject[$projectId]['invoiced_hours'] += $measured['invoiced'];
            $byProject[$projectId]['uninvoiced_hours'] += $measured['uninvoiced'];
        }

        return [
            'employee_id' => $employeeId,
            'from' => $from,
            'to' => $to,
            ...self::finishTotals($totals),
            'by_invoice_basis' => self::roundHours($byBasis),
            'by_project' => array_values(array_map(fn (array $project) => self::roundHours($project), $byProject)),
        ];
    }

    /**
     * Calculate invoiceability (facturabiliteit) for a team.
     *
     * Same rules as invoiceabilityForEmployee(). A shared Fixed line is split between the
     * employees on it in proportion to the hours each wrote there, so the per-employee
     * invoiced hours add up to the team total.
     *
     * @param  string     $from        Start date (Y-m-d)
     * @param  string     $to          End date (Y-m-d)
     * @param  int[]|null $employeeIds Employee IDs to include, or null for all
     * @return array{from: string, to: string, total_hours: float, billable_hours: float, invoiced_hours: float, uninvoiced_hours: float, exact_hours: float, estimated_hours: float, unmeasurable_hours: float, invoiceability_percentage: float, by_invoice_basis: array, by_employee: array}
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

        $totals = self::emptyInvoiceabilityTotals();
        $byBasis = self::emptyBasisTotals();
        $byEmployee = [];

        foreach (self::measureInvoicedHours($hours) as $measured) {
            self::addToTotals($totals, $measured);
            self::addToBasis($byBasis, $measured);

            $empId = self::resolveId($measured['hour']['employee'] ?? null);
            if ($empId === null) {
                continue;
            }

            $byEmployee[$empId] ??= ['employee_id' => $empId] + self::emptyInvoiceabilityTotals();
            self::addToTotals($byEmployee[$empId], $measured);
        }

        return [
            'from' => $from,
            'to' => $to,
            ...self::finishTotals($totals),
            'by_invoice_basis' => self::roundHours($byBasis),
            'by_employee' => array_values(array_map(fn (array $employee) => self::finishTotals($employee), $byEmployee)),
        ];
    }

    /**
     * Find hours without an invoice line within a date range.
     *
     * Returns hours with status DEFINITIVE or AUTHORIZED and no hour.invoiceline. Gripp never
     * sets hour.invoiceline on FIXED project lines, so every Fixed hour is listed here whether
     * its line was invoiced or not. Use invoiceabilityForEmployee() / invoiceabilityForTeam()
     * to see what was actually invoiced.
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
     * Work out, per hour, whether it is billable, how many hours of it were invoiced and how
     * sure that is.
     *
     * @return list<array{hour: array, amount: float, billable: bool, basis: ?string, invoiced: float, uninvoiced: float, precision: string}>
     */
    private static function measureInvoicedHours(Collection $hours): array
    {
        $lineLookup = self::resolveLineInvoiceBasis($hours);

        $fixedLineIds = [];
        foreach ($hours as $hour) {
            $lineId = self::resolveId($hour['offerprojectline'] ?? null);
            if ($lineId !== null && ($lineLookup[$lineId] ?? null) === self::INVOICEBASIS_FIXED) {
                $fixedLineIds[$lineId] = $lineId;
            }
        }
        $fixedLines = self::resolveFixedLines(array_values($fixedLineIds));

        $measured = [];
        foreach ($hours as $hour) {
            $amount = (float) ($hour['amount'] ?? 0);
            $lineId = self::resolveId($hour['offerprojectline'] ?? null);
            $basisId = $lineId !== null ? ($lineLookup[$lineId] ?? null) : null;

            $row = [
                'hour' => $hour,
                'amount' => $amount,
                'billable' => $basisId !== null && $basisId !== self::INVOICEBASIS_NONBILLABLE,
                'basis' => $basisId !== null ? (self::INVOICEBASIS_LABELS[$basisId] ?? null) : null,
                'invoiced' => 0.0,
                'uninvoiced' => 0.0,
                'precision' => self::PRECISION_EXACT,
            ];

            if ($basisId === self::INVOICEBASIS_COSTING || $basisId === self::INVOICEBASIS_BUDGETED) {
                $row['invoiced'] = (self::resolveId($hour['invoiceline'] ?? null) ?? 0) > 0 ? $amount : 0.0;
                $row['uninvoiced'] = $amount - $row['invoiced'];
            } elseif ($basisId === self::INVOICEBASIS_FIXED) {
                $line = $fixedLines[$lineId] ?? ['ratio' => 0.0, 'precision' => self::PRECISION_EXACT];
                $row['precision'] = $line['precision'];
                if ($line['precision'] !== self::PRECISION_UNMEASURABLE) {
                    $row['invoiced'] = $amount * $line['ratio'];
                    $row['uninvoiced'] = max(0.0, $amount - $row['invoiced']);
                }
            } elseif ($row['billable']) {
                // An invoice basis this class does not know: there is no telling what was invoiced.
                $row['precision'] = self::PRECISION_UNMEASURABLE;
            }

            $measured[] = $row;
        }

        return $measured;
    }

    /**
     * Compare every Fixed project line with what was invoiced on it, over its whole life.
     *
     * Gripp never links an hour on a Fixed line to an invoice line; the invoice line points at
     * the project line (invoiceline.part) with a quantity. So the only hour-level fact is the
     * ratio of hours invoiced on the line to hours written on it.
     *
     * @param  int[]  $lineIds
     * @return array<int, array{ratio: float, precision: string}>
     */
    private static function resolveFixedLines(array $lineIds): array
    {
        $written = [];
        $people = [];
        $invoiced = [];
        $invoicedInOtherUnits = [];

        foreach (array_chunk($lineIds, self::LINE_BATCH_SIZE) as $batch) {
            $hours = self::paginatedQuery('hour', [
                ['field' => 'hour.offerprojectline', 'operator' => 'in', 'value' => $batch],
            ]);
            foreach ($hours as $hour) {
                $lineId = self::resolveId($hour['offerprojectline'] ?? null);
                if ($lineId === null) {
                    continue;
                }
                $amount = (float) ($hour['amount'] ?? 0);
                $written[$lineId] = ($written[$lineId] ?? 0.0) + $amount;

                $employeeId = self::resolveId($hour['employee'] ?? null);
                if ($employeeId !== null && $amount > 0) {
                    $people[$lineId][$employeeId] = true;
                }
            }

            $invoiceLines = self::paginatedQuery('invoiceline', [
                ['field' => 'invoiceline.part', 'operator' => 'in', 'value' => $batch],
            ]);
            foreach ($invoiceLines as $invoiceLine) {
                $lineId = self::resolveId($invoiceLine['part'] ?? null);
                if ($lineId === null) {
                    continue;
                }
                $quantity = (float) ($invoiceLine['amount'] ?? 0);

                if (self::resolveId($invoiceLine['unit'] ?? null) === self::UNIT_HOUR) {
                    $invoiced[$lineId] = ($invoiced[$lineId] ?? 0.0) + $quantity;
                } elseif (abs($quantity * (float) ($invoiceLine['sellingprice'] ?? 0)) > 0.0001) {
                    // Invoiced as pieces or a price: real invoicing, but not in hours.
                    $invoicedInOtherUnits[$lineId] = true;
                }
            }
        }

        $lines = [];
        foreach ($lineIds as $lineId) {
            $hoursWritten = $written[$lineId] ?? 0.0;
            $hoursInvoiced = $invoiced[$lineId] ?? 0.0;

            $precision = match (true) {
                isset($invoicedInOtherUnits[$lineId]) => self::PRECISION_UNMEASURABLE,
                $hoursInvoiced != 0.0 && count($people[$lineId] ?? []) > 1 => self::PRECISION_ESTIMATED,
                default => self::PRECISION_EXACT,
            };

            $lines[$lineId] = [
                'ratio' => $hoursWritten > 0 ? max(0.0, $hoursInvoiced / $hoursWritten) : 0.0,
                'precision' => $precision,
            ];
        }

        return $lines;
    }

    /** @return array<string, float> */
    private static function emptyInvoiceabilityTotals(): array
    {
        return [
            'total_hours' => 0.0,
            'billable_hours' => 0.0,
            'invoiced_hours' => 0.0,
            'uninvoiced_hours' => 0.0,
            'exact_hours' => 0.0,
            'estimated_hours' => 0.0,
            'unmeasurable_hours' => 0.0,
        ];
    }

    /** @return array<string, array{billable_hours: float, invoiced_hours: float}> */
    private static function emptyBasisTotals(): array
    {
        $empty = ['billable_hours' => 0.0, 'invoiced_hours' => 0.0];

        return ['FIXED' => $empty, 'COSTING' => $empty, 'BUDGETED' => $empty];
    }

    /**
     * @param  array<string, mixed>  $totals
     * @param  array{amount: float, billable: bool, invoiced: float, uninvoiced: float, precision: string}  $measured
     */
    private static function addToTotals(array &$totals, array $measured): void
    {
        $totals['total_hours'] += $measured['amount'];

        if (! $measured['billable']) {
            return;
        }

        $totals['billable_hours'] += $measured['amount'];
        $totals['invoiced_hours'] += $measured['invoiced'];
        $totals['uninvoiced_hours'] += $measured['uninvoiced'];
        $totals[$measured['precision'] . '_hours'] += $measured['amount'];
    }

    /**
     * @param  array<string, array{billable_hours: float, invoiced_hours: float}>  $byBasis
     * @param  array{amount: float, billable: bool, basis: ?string, invoiced: float}  $measured
     */
    private static function addToBasis(array &$byBasis, array $measured): void
    {
        if (! $measured['billable'] || ! isset($byBasis[$measured['basis']])) {
            return;
        }

        $byBasis[$measured['basis']]['billable_hours'] += $measured['amount'];
        $byBasis[$measured['basis']]['invoiced_hours'] += $measured['invoiced'];
    }

    /**
     * Round the hour totals and add the percentage over the measurable billable hours.
     *
     * @param  array<string, mixed>  $totals
     * @return array<string, mixed>
     */
    private static function finishTotals(array $totals): array
    {
        $measurable = $totals['billable_hours'] - $totals['unmeasurable_hours'];

        return self::roundHours($totals) + [
            'invoiceability_percentage' => $measurable > 0
                ? round(($totals['invoiced_hours'] / $measurable) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * Round every float in a (nested) result to two decimals. Pro rata shares are products of
     * floats; unrounded they read as 23.999999999999996.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function roundHours(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_float($value)) {
                $values[$key] = round($value, 2);
            } elseif (is_array($value)) {
                $values[$key] = self::roundHours($value);
            }
        }

        return $values;
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
