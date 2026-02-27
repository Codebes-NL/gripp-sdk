<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Packet resource.
 *
 * @property-read string $createdon                Created timestamp.
 * @property-read string $updatedon                Updated timestamp.
 * @property-read int    $id                       Unique identifier.
 * @property      string $name                     Name.
 * @property      int    $number                   Number.
 * @property      int    $groupcategory            FK → Group category. Settings > Divers > Groepscategorie.
 * @property      string $description              Description (textmarkdown).
 * @property      int    $unit                     FK → Unit.
 * @property      string $internalnote             Internal note.
 * @property      bool   $hidedetails              Hide details.
 * @property      bool   $usepriceexceptionscustomer Use price exceptions customer.
 * @property      array  $tags                     FK[] → Tag.
 * @property      array  $packetlines              FK[] → PacketLine.
 * @property      string $extendedproperties       Extended properties.
 * @property      bool   $archived                 Archived.
 */
class Packet extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'                => 'datetime',
        'updatedon'                => 'datetime',
        'id'                       => 'int',
        'name'                     => 'string',
        'number'                   => 'int',
        'groupcategory'            => 'int',
        'description'              => 'string',
        'unit'                     => 'int',
        'internalnote'             => 'string',
        'hidedetails'              => 'boolean',
        'usepriceexceptionscustomer' => 'boolean',
        'tags'                     => 'array',
        'packetlines'              => 'array',
        'extendedproperties'       => 'string',
        'archived'                 => 'boolean',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
    ];

    public const RELATIONS = [
        'unit'        => Unit::class,
        'tags'        => Tag::class,
        'packetlines' => PacketLine::class,
    ];

    protected static function entity(): string
    {
        return 'packet';
    }
}
