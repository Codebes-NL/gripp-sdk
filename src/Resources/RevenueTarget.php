<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Revenuetarget resource (read-only).
 *
 * @property-read int   $id                Unique identifier.
 * @property-read int   $company           FK → Company (required).
 * @property-read int   $year              Year (required).
 * @property-read float $targetrevenue     Target revenue.
 * @property-read float $targetgrossprofit Target gross profit.
 */
class RevenueTarget extends Resource
{
    use CanRead;

    const FIELDS = [
        'id'                => 'int',
        'company'           => 'int',
        'year'              => 'int',
        'targetrevenue'     => 'float',
        'targetgrossprofit' => 'float',
    ];

    const READONLY = [
        'id',
        'company',
        'year',
        'targetrevenue',
        'targetgrossprofit',
    ];

    const RELATIONS = [
        'company' => Company::class,
    ];

    protected static function entity(): string
    {
        return 'revenuetarget';
    }
}
