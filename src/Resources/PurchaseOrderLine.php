<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Purchaseorderline resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property      int    $_ordering          Ordering.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      float  $amount             Amount.
 * @property      string $unit               Unit.
 * @property      float  $sellingprice       Selling price.
 * @property      float  $discount           Discount.
 * @property      string $additionalsubject  Additional subject.
 * @property      string $description        Description.
 * @property      int    $vat                FK → VAT rate (required). Settings > BTW-tarieven.
 * @property      int    $costheading        FK → CostHeading. Settings > Kostenplaatsen.
 * @property      string $ordercodesupplier  Order code supplier.
 * @property      int    $ledger             FK → Ledger. Settings > Grootboeken.
 * @property      int    $purchaseorder      FK → PurchaseOrder (required).
 * @property      int    $projectline        FK → OfferProjectLine.
 * @property      int    $project            FK → Project (offer or project).
 * @property      string $extendedproperties Extended properties.
 */
class PurchaseOrderLine extends Resource
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
        'amount'             => 'float',
        'unit'               => 'string',
        'sellingprice'       => 'float',
        'discount'           => 'float',
        'additionalsubject'  => 'string',
        'description'        => 'string',
        'vat'                => 'int',
        'costheading'        => 'int',
        'ordercodesupplier'  => 'string',
        'ledger'             => 'int',
        'purchaseorder'      => 'int',
        'projectline'        => 'int',
        'project'            => 'int',
        'extendedproperties' => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const REQUIRED = [
        'vat',
        'purchaseorder',
    ];

    public const RELATIONS = [
        'costheading'   => CostHeading::class,
        'ledger'        => Ledger::class,
        'purchaseorder' => PurchaseOrder::class,
        'projectline'   => OfferProjectLine::class,
        'project'       => Project::class,
    ];

    protected static function entity(): string
    {
        return 'purchaseorderline';
    }
}
