<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * EmployeeYearlyLeaveBudget resource (no delete).
 *
 * @property-read int    $id                 Unique identifier.
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $balance            Balance (calculated automatically).
 * @property      string $leavetype          Leave type: VACATIONHOURS | OVERTIME.
 * @property      int    $employee           FK → Employee (required).
 * @property      int    $year               Year.
 * @property      int    $newsaldothisyear   New saldo this year.
 * @property      int    $takeoverlastyear   Takeover last year.
 * @property      string $note               Note.
 * @property      string $extendedproperties Extended properties.
 */
class EmployeeYearlyLeaveBudget extends Resource
{
    use CanCreate, CanRead, CanUpdate;

    const FIELDS = [
        'id'                 => 'int',
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'leavetype'          => 'string',
        'employee'           => 'int',
        'year'               => 'int',
        'newsaldothisyear'   => 'int',
        'takeoverlastyear'   => 'int',
        'note'               => 'string',
        'balance'            => 'int',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'id',
        'createdon',
        'updatedon',
        'balance',
    ];

    const REQUIRED = [
        'employee',
    ];

    const RELATIONS = [
        'employee' => Employee::class,
    ];

    protected static function entity(): string
    {
        return 'employeeYearlyLeaveBudget';
    }
}
