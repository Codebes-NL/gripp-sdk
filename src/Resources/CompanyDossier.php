<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Companydossier resource.
 *
 * @property-read string $createdon   Created timestamp.
 * @property-read string $updatedon   Updated timestamp.
 * @property-read int    $id          Unique identifier.
 * @property-read string $searchname  Search name.
 * @property      int    $company     FK → Company (required).
 * @property      string $date        Date.
 * @property      string $name        Name.
 * @property      string $note        Note.
 * @property      array  $files       FK[] → File.
 */
class CompanyDossier extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'  => 'datetime',
        'updatedon'  => 'datetime',
        'id'         => 'int',
        'searchname' => 'string',
        'company'    => 'int',
        'date'       => 'date',
        'name'       => 'string',
        'note'       => 'string',
        'files'      => 'array',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const REQUIRED = [
        'company',
    ];

    public const RELATIONS = [
        'company' => Company::class,
        'files'   => File::class,
    ];

    protected static function entity(): string
    {
        return 'companydossier';
    }
}
