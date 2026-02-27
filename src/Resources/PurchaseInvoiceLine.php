<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Purchaseinvoiceline resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property      int    $_ordering          Ordering.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $purchaseinvoice    FK → PurchaseInvoice (required).
 * @property      string $additionalsubject  Additional subject.
 * @property      float  $amount             Amount (required).
 * @property      float  $sellingprice       Selling price (required).
 * @property      float  $discount           Discount (required).
 * @property      int    $vat                FK → VAT rate (required). Settings > BTW-tarieven.
 * @property      int    $costheading        FK → CostHeading. Settings > Kostenplaatsen.
 * @property      string $description        Description.
 * @property      int    $ledger             FK → Ledger. Settings > Grootboeken.
 * @property      int    $purchaseorderline  FK → PurchaseOrderLine.
 * @property      int    $project            FK → Project (offer or project).
 * @property      int    $part               FK → OfferProjectLine.
 * @property      string $extendedproperties Extended properties.
 */
class PurchaseInvoiceLine extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        '_ordering'          => 'int',
        'id'                 => 'int',
        'searchname'         => 'string',
        'purchaseinvoice'    => 'int',
        'additionalsubject'  => 'string',
        'amount'             => 'float',
        'sellingprice'       => 'float',
        'discount'           => 'float',
        'vat'                => 'int',
        'costheading'        => 'int',
        'description'        => 'string',
        'ledger'             => 'int',
        'purchaseorderline'  => 'int',
        'project'            => 'int',
        'part'               => 'int',
        'extendedproperties' => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const REQUIRED = [
        'purchaseinvoice',
        'amount',
        'sellingprice',
        'discount',
        'vat',
    ];

    public const RELATIONS = [
        'purchaseinvoice'   => PurchaseInvoice::class,
        'costheading'       => CostHeading::class,
        'ledger'            => Ledger::class,
        'purchaseorderline' => PurchaseOrderLine::class,
        'project'           => Project::class,
        'part'              => OfferProjectLine::class,
    ];

    protected static function entity(): string
    {
        return 'purchaseinvoiceline';
    }
}
