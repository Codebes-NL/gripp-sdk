<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Packetline resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property      int    $_ordering          Ordering.
 * @property-read string $searchname         Search name.
 * @property      int    $packet             FK → Packet.
 * @property      int    $product            FK → Product.
 * @property      string $convertto          Convert to: PROJECTORINVOICE | CONTRACTFORNEWINVOICE | CONTRACTFORNEWINVOICEONCE | CONTRACTFORNEWPROJECT | CONTRACTFORUPDATEPROJECT | CONTRACTFORNEWPROJECTONCE.
 * @property      float  $amount             Amount.
 * @property      int    $unit               FK → Unit.
 * @property      float  $sellingprice       Selling price.
 * @property      float  $discount           Discount.
 * @property      int    $vat                FK → VAT rate. Settings > BTW-tarieven.
 * @property      string $invoicebasis       Invoice basis: FIXED | COSTING | BUDGETED | NONBILLABLE.
 * @property      float  $buyingprice        Buying price.
 * @property      string $additionalsubject  Additional subject.
 * @property      string $description        Description (textmarkdown).
 * @property      string $extendedproperties Extended properties.
 */
class PacketLine extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        '_ordering'          => 'int',
        'searchname'         => 'string',
        'packet'             => 'int',
        'product'            => 'int',
        'convertto'          => 'string',
        'amount'             => 'float',
        'unit'               => 'int',
        'sellingprice'       => 'float',
        'discount'           => 'float',
        'vat'                => 'int',
        'invoicebasis'       => 'string',
        'buyingprice'        => 'float',
        'additionalsubject'  => 'string',
        'description'        => 'string',
        'extendedproperties' => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const RELATIONS = [
        'packet'  => Packet::class,
        'product' => Product::class,
        'unit'    => Unit::class,
    ];

    protected static function entity(): string
    {
        return 'packetline';
    }
}
