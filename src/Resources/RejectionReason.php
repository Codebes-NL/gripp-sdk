<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Rejectionreason resource.
 *
 * @property-read int    $id       Unique identifier.
 * @property-read string $zoeknaam Search name.
 * @property      string $naam     Name.
 */
class RejectionReason extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'id'       => 'int',
        'zoeknaam' => 'string',
        'naam'     => 'string',
    ];

    const READONLY = [
        'id',
        'zoeknaam',
    ];

    protected static function entity(): string
    {
        return 'rejectionreason';
    }
}
