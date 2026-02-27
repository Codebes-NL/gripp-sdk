<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Offerprojectline resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property      int    $_ordering          Ordering.
 * @property-read int    $id                 Unique identifier.
 * @property      bool   $hidedetails        Hide details.
 * @property      int    $groupcategory      FK → Group category. Settings > Divers > Groepscategorie.
 * @property-read string $searchname         Search name.
 * @property      bool   $hidefortimewriting Hide for time writing.
 * @property      float  $amount             Amount (required).
 * @property-read mixed  $amountwritten      Amount written (subquery).
 * @property      string $convertto          Convert to: PROJECTORINVOICE | CONTRACTFORNEWINVOICE | CONTRACTFORNEWINVOICEONCE | CONTRACTFORNEWPROJECT | CONTRACTFORUPDATEPROJECT | CONTRACTFORNEWPROJECTONCE.
 * @property      int    $unit               FK → Unit.
 * @property      float  $sellingprice       Selling price (required).
 * @property      float  $discount           Discount (required).
 * @property      string $invoicebasis       Invoice basis (required): FIXED | COSTING | BUDGETED | NONBILLABLE.
 * @property      string $additionalsubject  Additional subject.
 * @property      string $description        Description.
 * @property      int    $vat                FK → VAT rate (required). Settings > BTW-tarieven.
 * @property      string $extendedproperties Extended properties.
 * @property      string $internalnote       Internal note.
 * @property      string $rowtype            Row type: NORMAL | GROUP.
 * @property      int    $offerprojectbase   FK → Offer/Project ID (required).
 * @property      int    $contractline       FK → ContractLine.
 * @property      float  $buyingprice        Buying price (required).
 * @property      int    $product            FK → Product (required).
 */
class OfferProjectLine extends Resource
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
        'hidedetails'        => 'boolean',
        'groupcategory'      => 'int',
        'searchname'         => 'string',
        'hidefortimewriting' => 'boolean',
        'amount'             => 'float',
        'amountwritten'      => 'subquery',
        'convertto'          => 'string',
        'unit'               => 'int',
        'sellingprice'       => 'float',
        'discount'           => 'float',
        'invoicebasis'       => 'string',
        'additionalsubject'  => 'string',
        'description'        => 'string',
        'vat'                => 'int',
        'extendedproperties' => 'string',
        'internalnote'       => 'string',
        'rowtype'            => 'string',
        'offerprojectbase'   => 'int',
        'contractline'       => 'int',
        'buyingprice'        => 'float',
        'product'            => 'int',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'amountwritten',
    ];

    public const REQUIRED = [
        'amount',
        'sellingprice',
        'discount',
        'invoicebasis',
        'vat',
        'offerprojectbase',
        'buyingprice',
        'product',
    ];

    public const RELATIONS = [
        'unit'         => Unit::class,
        'contractline' => ContractLine::class,
        'product'      => Product::class,
    ];

    protected static function entity(): string
    {
        return 'offerprojectline';
    }
}
