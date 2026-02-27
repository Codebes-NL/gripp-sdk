<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Employeefamily resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $employee           FK → Employee (required).
 * @property      string $name               Name.
 * @property      string $date               Date.
 * @property      string $phone              Phone number.
 * @property      string $extendedproperties Extended properties.
 */
class EmployeeFamily extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'searchname'         => 'string',
        'employee'           => 'int',
        'name'               => 'string',
        'date'               => 'date',
        'phone'              => 'string',
        'extendedproperties' => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const REQUIRED = [
        'employee',
    ];

    public const RELATIONS = [
        'employee' => Employee::class,
    ];

    protected static function entity(): string
    {
        return 'employeefamily';
    }
}
