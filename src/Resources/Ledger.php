<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Ledger resource.
 *
 * @property-read string $createdon   Created timestamp.
 * @property-read string $updatedon   Updated timestamp.
 * @property-read int    $id          Unique identifier.
 * @property-read string $searchname  Search name.
 * @property      string $name        Name.
 * @property      string $number      Number.
 * @property-read int    $type        FK → Grootboektype.
 * @property-read int    $debitcredit FK → Debetcredit.
 * @property      int    $categorie   FK → Category number of a ledger within the application.
 * @property      bool   $active      Active.
 */
class Ledger extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'   => 'datetime',
        'updatedon'   => 'datetime',
        'id'          => 'int',
        'searchname'  => 'string',
        'name'        => 'string',
        'number'      => 'string',
        'type'        => 'int',
        'debitcredit' => 'int',
        'categorie'   => 'int',
        'active'      => 'boolean',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'type',
        'debitcredit',
    ];

    protected static function entity(): string
    {
        return 'ledger';
    }
}
