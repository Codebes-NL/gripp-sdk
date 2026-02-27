<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Purchasepayment resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property      int    $_ordering          Ordering.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $purchaseinvoice    FK → PurchaseInvoice (required).
 * @property      string $date               Date (required).
 * @property      float  $amount             Amount (required).
 * @property      string $notes              Notes.
 * @property      string $extendedproperties Extended properties.
 */
class PurchasePayment extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        '_ordering'          => 'int',
        'id'                 => 'int',
        'searchname'         => 'string',
        'purchaseinvoice'    => 'int',
        'date'               => 'date',
        'amount'             => 'float',
        'notes'              => 'string',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    const REQUIRED = [
        'purchaseinvoice',
        'date',
        'amount',
    ];

    const RELATIONS = [
        'purchaseinvoice' => PurchaseInvoice::class,
    ];

    protected static function entity(): string
    {
        return 'purchasepayment';
    }
}
