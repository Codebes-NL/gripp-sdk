<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Hour resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $task               FK → Task.
 * @property      string $status             Status: CONCEPT | DEFINITIVE | AUTHORIZED.
 * @property      string $date               Date.
 * @property      string $description        Description.
 * @property      float  $amount             Amount.
 * @property      int    $employee           FK → Employee (required).
 * @property      int    $offerprojectbase   FK → Offer/Project ID (required).
 * @property      int    $offerprojectline   FK → OfferProjectLine.
 * @property      int    $invoiceline        FK → InvoiceLine.
 * @property      string $authorizedon       Authorized on date.
 * @property      int    $authorizedby       FK → Employee.
 * @property      int    $definitiveby       FK → Employee.
 * @property      string $definitiveon       Definitive on date.
 * @property      string $extendedproperties Extended properties.
 */
class Hour extends Resource
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
        'task'               => 'int',
        'status'             => 'string',
        'date'               => 'date',
        'description'        => 'string',
        'amount'             => 'float',
        'employee'           => 'int',
        'offerprojectbase'   => 'int',
        'offerprojectline'   => 'int',
        'invoiceline'        => 'int',
        'authorizedon'       => 'date',
        'authorizedby'       => 'int',
        'definitiveby'       => 'int',
        'definitiveon'       => 'date',
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
        'offerprojectbase',
    ];

    public const RELATIONS = [
        'task'             => Task::class,
        'employee'         => Employee::class,
        'authorizedby'     => Employee::class,
        'definitiveby'     => Employee::class,
        'offerprojectline' => OfferProjectLine::class,
        'invoiceline'      => InvoiceLine::class,
    ];

    protected static function entity(): string
    {
        return 'hour';
    }
}
