<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Absencerequest resource.
 *
 * @property-read string   $createdon          Created timestamp.
 * @property-read string   $updatedon          Updated timestamp.
 * @property-read int      $id                 Unique identifier.
 * @property-read string   $searchname         Search name.
 * @property      int      $employee           FK → Employee.
 * @property      int|null $absencetype        FK → Absence type (Settings > Leave).
 * @property      string   $description        Description.
 * @property      string   $comment            Comment.
 * @property      string   $extendedproperties Extended properties.
 * @property      array    $absencerequestline FK[] → AbsenceRequestLine.
 */
class AbsenceRequest extends Resource
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
        'absencetype'        => 'int',
        'description'        => 'string',
        'comment'            => 'string',
        'extendedproperties' => 'string',
        'absencerequestline' => 'array',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const RELATIONS = [
        'employee'           => Employee::class,
        'absencerequestline' => AbsenceRequestLine::class,
    ];

    protected static function entity(): string
    {
        return 'absencerequest';
    }
}
