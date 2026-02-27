<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Bulkprice resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property      int    $product            FK → Product.
 * @property      float  $amount             Amount.
 * @property      float  $sellingprice       Selling price.
 * @property      float  $purchaseprice      Purchase price.
 * @property      float  $margin             Margin.
 * @property      string $extendedproperties Extended properties.
 */
class BulkPrice extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'product'            => 'int',
        'amount'             => 'float',
        'sellingprice'       => 'float',
        'purchaseprice'      => 'float',
        'margin'             => 'float',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
    ];

    const RELATIONS = [
        'product' => Product::class,
    ];

    protected static function entity(): string
    {
        return 'bulkprice';
    }
}
