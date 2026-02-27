<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Employeetarget resource (read-only).
 *
 * @property-read int   $id                 Unique identifier.
 * @property      int   $employee           FK → Employee (required).
 * @property      int   $year               Year.
 * @property      float $targetsales        Target sales.
 * @property      float $targetrevenue      Target revenue.
 * @property      float $targetgrossprofit  Target gross profit.
 * @property      float $targethourlyrate   Target hourly rate.
 * @property      float $targetbillability  Target billability.
 */
class EmployeeTarget extends Resource
{
    use CanRead;

    public const FIELDS = [
        'id'                => 'int',
        'employee'          => 'int',
        'year'              => 'int',
        'targetsales'       => 'float',
        'targetrevenue'     => 'float',
        'targetgrossprofit' => 'float',
        'targethourlyrate'  => 'float',
        'targetbillability' => 'float',
    ];

    public const READONLY = [
        'id',
    ];

    public const RELATIONS = [
        'employee' => Employee::class,
    ];

    protected static function entity(): string
    {
        return 'employeetarget';
    }
}
