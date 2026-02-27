<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Absencerequestline resource.
 *
 * @property-read string $createdon             Created timestamp.
 * @property-read string $updatedon             Updated timestamp.
 * @property-read int    $id                    Unique identifier.
 * @property-read string $searchname            Search name.
 * @property      string $date                  Date.
 * @property      string $description           Description.
 * @property      float  $amount                Amount.
 * @property      string $startingtime          Starting time.
 * @property      int    $absencerequest        FK → AbsenceRequest.
 * @property      string $absencerequeststatus  Status: PENDING | APPROVED | REJECTED.
 * @property      string $extendedproperties    Extended properties.
 */
class AbsenceRequestLine extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'            => 'datetime',
        'updatedon'            => 'datetime',
        'id'                   => 'int',
        'searchname'           => 'string',
        'date'                 => 'date',
        'description'          => 'string',
        'amount'               => 'float',
        'startingtime'         => 'time',
        'absencerequest'       => 'int',
        'absencerequeststatus' => 'string',
        'extendedproperties'   => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    const RELATIONS = [
        'absencerequest' => AbsenceRequest::class,
    ];

    protected static function entity(): string
    {
        return 'absencerequestline';
    }
}
