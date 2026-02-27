<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Cost resource (read-only).
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $number             Number.
 * @property-read string $date               Date.
 * @property-read string $description        Description.
 * @property-read int    $identity           FK → Identity (Settings > Identities & Templates).
 * @property-read float  $value              Value.
 * @property-read int    $ledger             FK → Ledger (Settings > Grootboeken).
 * @property-read int    $costheading        FK → Cost heading (Settings > Kostenplaatsen).
 * @property-read string $extendedproperties Extended properties.
 */
class Cost extends Resource
{
    use CanRead;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'number'             => 'string',
        'date'               => 'date',
        'description'        => 'string',
        'identity'           => 'int',
        'value'              => 'float',
        'ledger'             => 'int',
        'costheading'        => 'int',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'number',
        'date',
        'description',
        'identity',
        'value',
        'ledger',
        'costheading',
        'extendedproperties',
    ];

    const RELATIONS = [
        'ledger'      => Ledger::class,
        'costheading' => CostHeading::class,
    ];

    protected static function entity(): string
    {
        return 'cost';
    }
}
