<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Yeartargettype resource (read-only).
 *
 * @property-read int    $id   Unique identifier.
 * @property-read string $name Name.
 */
class YearTargetType extends Resource
{
    use CanRead;

    public const FIELDS = [
        'id'   => 'int',
        'name' => 'string',
    ];

    public const READONLY = [
        'id',
        'name',
    ];

    protected static function entity(): string
    {
        return 'yeartargettype';
    }
}
