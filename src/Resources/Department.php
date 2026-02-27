<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Department resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property      int    $_ordering          Ordering.
 * @property      string $name               Name.
 * @property      string $extendedproperties Extended properties.
 */
class Department extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        '_ordering'          => 'int',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'name'               => 'string',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
    ];

    protected static function entity(): string
    {
        return 'department';
    }
}
