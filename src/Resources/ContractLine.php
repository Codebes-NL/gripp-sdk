<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Contractline resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $_ordering          Ordering.
 * @property      int    $contract           FK → Contract (required).
 * @property      int    $product            FK → Product (required).
 * @property      string $convertto          Convert to (required): PROJECTORINVOICE | CONTRACTFORNEWINVOICE | CONTRACTFORNEWINVOICEONCE | CONTRACTFORNEWPROJECT | CONTRACTFORUPDATEPROJECT | CONTRACTFORNEWPROJECTONCE.
 * @property      bool   $invoicedirectly    Invoice directly.
 * @property      int    $groupcategory      FK → Group category (Settings > Divers > Groepscategorie).
 * @property      float  $amount             Amount (required).
 * @property      int    $unit               FK → Unit.
 * @property      float  $sellingprice       Selling price (required).
 * @property      float  $discount           Discount (required).
 * @property      float  $buyingprice        Buying price (required).
 * @property      string $additionalsubject  Additional subject.
 * @property      string $description        Description.
 * @property      int    $vat                FK → VAT rate (required). Settings > BTW-tarieven.
 * @property      string $invoicebasis       Invoice basis (required): FIXED | COSTING | BUDGETED | NONBILLABLE.
 * @property      string $rowtype            Row type: NORMAL | GROUP.
 * @property      string $startdate          Start date.
 * @property      string $enddate            End date.
 * @property      string $extendedproperties Extended properties.
 * @property      bool   $hidedetails        Hide details.
 */
class ContractLine extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        '_ordering'          => 'int',
        'id'                 => 'int',
        'searchname'         => 'string',
        'contract'           => 'int',
        'product'            => 'int',
        'convertto'          => 'string',
        'invoicedirectly'    => 'boolean',
        'groupcategory'      => 'int',
        'amount'             => 'float',
        'unit'               => 'int',
        'sellingprice'       => 'float',
        'discount'           => 'float',
        'buyingprice'        => 'float',
        'additionalsubject'  => 'string',
        'description'        => 'string',
        'vat'                => 'int',
        'invoicebasis'       => 'string',
        'rowtype'            => 'string',
        'startdate'          => 'date',
        'enddate'            => 'date',
        'extendedproperties' => 'string',
        'hidedetails'        => 'boolean',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    const REQUIRED = [
        'contract',
        'product',
        'convertto',
        'amount',
        'sellingprice',
        'discount',
        'buyingprice',
        'vat',
        'invoicebasis',
    ];

    const RELATIONS = [
        'contract' => Contract::class,
        'product'  => Product::class,
        'unit'     => Unit::class,
    ];

    protected static function entity(): string
    {
        return 'contractline';
    }
}
