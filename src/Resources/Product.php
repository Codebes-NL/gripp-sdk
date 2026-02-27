<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Product resource.
 *
 * @property-read int    $id                       Unique identifier.
 * @property-read string $createdon                Created timestamp.
 * @property-read string $updatedon                Updated timestamp.
 * @property-read string $searchname               Search name.
 * @property      string $customfields             Custom fields.
 * @property      string $name                     Name (required).
 * @property      int    $unit                     FK → Unit (required).
 * @property      float  $sellingprice             Selling price.
 * @property      string $convertto                Convert to: PROJECTORINVOICE | CONTRACTFORNEWINVOICE | CONTRACTFORNEWINVOICEONCE | CONTRACTFORNEWPROJECT | CONTRACTFORUPDATEPROJECT | CONTRACTFORNEWPROJECTONCE.
 * @property      int    $vat                      FK → VAT rate (required). Settings > BTW-tarieven.
 * @property      int    $buyingvat                FK → VAT rate (buying). Settings > BTW-tarieven.
 * @property      int    $tasktype                 FK → TaskType.
 * @property      int    $costheading              FK → CostHeading. Settings > Kostenplaatsen.
 * @property      int    $ledger                   FK → Ledger. Settings > Grootboeken.
 * @property      string $internalnote             Internal note.
 * @property      bool   $hideontimetrackingform   Hide on time tracking form.
 * @property      int    $purchaseledger           FK → Purchase ledger. Settings > Grootboeken.
 * @property      string $invoicebasis             Invoice basis (required): FIXED | COSTING | BUDGETED | NONBILLABLE.
 * @property      string $description              Description.
 * @property-read int    $number                   Number.
 * @property      bool   $archived                 Archived.
 * @property      string $extendedproperties       Extended properties.
 * @property      string $supplierordercode        Supplier order code.
 * @property      int    $supplier                 FK → User (supplier).
 * @property      array  $tags                     FK[] → Tag.
 * @property      float  $buyingprice              Buying price.
 * @property      array  $attachments_internal     FK[] → File.
 * @property      array  $attachments_external     FK[] → File.
 */
class Product extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'id'                       => 'int',
        'createdon'                => 'datetime',
        'updatedon'                => 'datetime',
        'searchname'               => 'string',
        'customfields'             => 'customfields',
        'name'                     => 'string',
        'unit'                     => 'int',
        'sellingprice'             => 'float',
        'convertto'                => 'string',
        'vat'                      => 'int',
        'buyingvat'                => 'int',
        'tasktype'                 => 'int',
        'costheading'              => 'int',
        'ledger'                   => 'int',
        'internalnote'             => 'string',
        'hideontimetrackingform'   => 'boolean',
        'purchaseledger'           => 'int',
        'invoicebasis'             => 'string',
        'description'              => 'string',
        'number'                   => 'int',
        'archived'                 => 'boolean',
        'extendedproperties'       => 'string',
        'supplierordercode'        => 'string',
        'supplier'                 => 'int',
        'tags'                     => 'array',
        'buyingprice'              => 'float',
        'attachments_internal'     => 'array',
        'attachments_external'     => 'array',
    ];

    const READONLY = [
        'id',
        'createdon',
        'updatedon',
        'searchname',
        'number',
    ];

    const REQUIRED = [
        'name',
        'unit',
        'vat',
        'invoicebasis',
    ];

    const RELATIONS = [
        'unit'                 => Unit::class,
        'tasktype'             => TaskType::class,
        'costheading'          => CostHeading::class,
        'ledger'               => Ledger::class,
        'tags'                 => Tag::class,
        'attachments_internal' => File::class,
        'attachments_external' => File::class,
    ];

    protected static function entity(): string
    {
        return 'product';
    }
}
