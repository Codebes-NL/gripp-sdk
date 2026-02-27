<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Invoiceline resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $_ordering          Ordering.
 * @property      int    $groepscategorie    FK → Group category (Settings > Divers > Groepscategorie).
 * @property      int    $invoice            FK → Invoice (required).
 * @property      int    $ledger             FK → Ledger (Settings > Grootboeken).
 * @property      int    $costheading        FK → Cost heading (Settings > Kostenplaatsen).
 * @property      int    $product            FK → Product (required).
 * @property      float  $amount             Amount (required).
 * @property      int    $unit               FK → Unit.
 * @property      float  $sellingprice       Selling price (required).
 * @property      float  $discount           Discount (required).
 * @property      float  $buyingprice        Buying price (required).
 * @property      string $additionalsubject  Additional subject.
 * @property      string $description        Description.
 * @property      int    $vat                FK → VAT rate (required). Settings > BTW-tarieven.
 * @property      int    $project            FK → Project.
 * @property      int    $umbrellaproject    FK → UmbrellaProject.
 * @property      int    $part               FK → OfferProjectLine.
 * @property      int    $contractline       FK → ContractLine.
 * @property      int    $contract           FK → Contract.
 * @property      string $rowtype            Row type: NORMAL | GROUP.
 * @property      bool   $hidedetails        Hide details.
 * @property      string $extendedproperties Extended properties.
 */
class InvoiceLine extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        '_ordering'          => 'int',
        'searchname'         => 'string',
        'groepscategorie'    => 'int',
        'invoice'            => 'int',
        'ledger'             => 'int',
        'costheading'        => 'int',
        'product'            => 'int',
        'amount'             => 'float',
        'unit'               => 'int',
        'sellingprice'       => 'float',
        'discount'           => 'float',
        'buyingprice'        => 'float',
        'additionalsubject'  => 'string',
        'description'        => 'string',
        'vat'                => 'int',
        'project'            => 'int',
        'umbrellaproject'    => 'int',
        'part'               => 'int',
        'contractline'       => 'int',
        'contract'           => 'int',
        'rowtype'            => 'string',
        'hidedetails'        => 'boolean',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    const REQUIRED = [
        'invoice',
        'product',
        'amount',
        'sellingprice',
        'discount',
        'buyingprice',
        'vat',
    ];

    const RELATIONS = [
        'invoice'         => Invoice::class,
        'product'         => Product::class,
        'unit'            => Unit::class,
        'project'         => Project::class,
        'umbrellaproject' => UmbrellaProject::class,
        'part'            => OfferProjectLine::class,
        'contractline'    => ContractLine::class,
        'contract'        => Contract::class,
        'ledger'          => Ledger::class,
        'costheading'     => CostHeading::class,
    ];

    protected static function entity(): string
    {
        return 'invoiceline';
    }
}
