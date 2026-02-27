<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Yeartarget resource (read-only).
 *
 * @property-read int   $id             Unique identifier.
 * @property-read int   $year           Year.
 * @property-read float $month1         Month 1 (January).
 * @property-read float $month2         Month 2 (February).
 * @property-read float $month3         Month 3 (March).
 * @property-read float $month4         Month 4 (April).
 * @property-read float $month5         Month 5 (May).
 * @property-read float $month6         Month 6 (June).
 * @property-read float $month7         Month 7 (July).
 * @property-read float $month8         Month 8 (August).
 * @property-read float $month9         Month 9 (September).
 * @property-read float $month10        Month 10 (October).
 * @property-read float $month11        Month 11 (November).
 * @property-read float $month12        Month 12 (December).
 * @property-read int   $identity       FK → Identiteit.
 * @property-read int   $yeartargettype FK → YearTargetType.
 */
class YearTarget extends Resource
{
    use CanRead;

    public const FIELDS = [
        'id'             => 'int',
        'year'           => 'int',
        'month1'         => 'float',
        'month2'         => 'float',
        'month3'         => 'float',
        'month4'         => 'float',
        'month5'         => 'float',
        'month6'         => 'float',
        'month7'         => 'float',
        'month8'         => 'float',
        'month9'         => 'float',
        'month10'        => 'float',
        'month11'        => 'float',
        'month12'        => 'float',
        'identity'       => 'int',
        'yeartargettype' => 'int',
    ];

    public const READONLY = [
        'id',
        'year',
        'month1',
        'month2',
        'month3',
        'month4',
        'month5',
        'month6',
        'month7',
        'month8',
        'month9',
        'month10',
        'month11',
        'month12',
        'identity',
        'yeartargettype',
    ];

    public const RELATIONS = [
        'yeartargettype' => YearTargetType::class,
    ];

    protected static function entity(): string
    {
        return 'yeartarget';
    }
}
