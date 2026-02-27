<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Tasktype resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property      int    $_ordering          Ordering.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      string $name               Name.
 * @property      string $color              Color.
 * @property      string $extendedproperties Extended properties.
 */
class TaskType extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        '_ordering'          => 'int',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'searchname'         => 'string',
        'name'               => 'string',
        'color'              => 'color',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    protected static function entity(): string
    {
        return 'tasktype';
    }
}
