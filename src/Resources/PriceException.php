<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Priceexception resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property      int    $company            FK → Company.
 * @property      int    $product            FK → Product.
 * @property      float  $sellingprice       Selling price.
 * @property      string $extendedproperties Extended properties.
 */
class PriceException extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'company'            => 'int',
        'product'            => 'int',
        'sellingprice'       => 'float',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
    ];

    const RELATIONS = [
        'company' => Company::class,
        'product' => Product::class,
    ];

    protected static function entity(): string
    {
        return 'priceexception';
    }
}
