<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanRead;

/**
 * Memorial resource (read-only).
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $reportdate         Report date (required).
 * @property-read int    $identity           FK → Identiteit (required).
 * @property-read int    $number             Number (required).
 * @property-read string $name               Name (required).
 * @property-read string $description        Description (required).
 * @property-read array  $files              FK[] → File.
 * @property-read array  $tags               FK[] → Tag.
 * @property-read bool   $isbasis            Is basis.
 * @property-read int    $type               FK → Memoriaaltype (required).
 * @property-read array  $memoriallines      FK[] → MemorialLine.
 * @property-read string $extendedproperties Extended properties.
 */
class Memorial extends Resource
{
    use CanRead;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'reportdate'         => 'date',
        'identity'           => 'int',
        'number'             => 'int',
        'name'               => 'string',
        'description'        => 'string',
        'files'              => 'array',
        'tags'               => 'array',
        'isbasis'            => 'boolean',
        'type'               => 'int',
        'memoriallines'      => 'array',
        'extendedproperties' => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'reportdate',
        'identity',
        'number',
        'name',
        'description',
        'files',
        'tags',
        'isbasis',
        'type',
        'memoriallines',
        'extendedproperties',
    ];

    public const RELATIONS = [
        'files'         => File::class,
        'tags'          => Tag::class,
        'memoriallines' => MemorialLine::class,
    ];

    protected static function entity(): string
    {
        return 'memorial';
    }
}
