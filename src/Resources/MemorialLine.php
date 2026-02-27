<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Memorialline resource (read-only).
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $description        Description (required).
 * @property-read float  $value              Value (required).
 * @property-read int    $ledger             FK → Ledger (required). Settings > Grootboeken.
 * @property-read int    $costheading        FK → Cost heading. Settings > Kostenplaatsen.
 * @property-read int    $vat                FK → VAT rate. Settings > BTW-tarieven.
 * @property-read int    $memorial           FK → Memorial (required).
 * @property-read string $extendedproperties Extended properties.
 */
class MemorialLine extends Resource
{
    use CanRead;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'description'        => 'string',
        'value'              => 'float',
        'ledger'             => 'int',
        'costheading'        => 'int',
        'vat'                => 'int',
        'memorial'           => 'int',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'description',
        'value',
        'ledger',
        'costheading',
        'vat',
        'memorial',
        'extendedproperties',
    ];

    const RELATIONS = [
        'ledger'     => Ledger::class,
        'costheading' => CostHeading::class,
        'memorial'   => Memorial::class,
    ];

    protected static function entity(): string
    {
        return 'memorialline';
    }
}
