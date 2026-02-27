<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Umbrellaproject resource (no delete).
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      int    $number             Number.
 * @property      string $name               Name.
 * @property      int    $phase              FK → ProjectPhase (projectfase).
 * @property      string $startdate          Start date.
 * @property      string $deadline           Deadline.
 * @property      string $deliverydate       Delivery date.
 * @property      string $enddate            End date.
 * @property      string $description        Description (textmarkdown).
 * @property      string $internalnote       Internal note.
 * @property      array  $tags               FK[] → Tag.
 * @property      string $archivedon         Archived on date.
 * @property      string $customfields       Custom fields.
 * @property      float  $budget             Budget.
 */
class UmbrellaProject extends Resource
{
    use CanCreate, CanRead, CanUpdate;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'searchname'         => 'string',
        'number'             => 'int',
        'name'               => 'string',
        'phase'              => 'int',
        'startdate'          => 'date',
        'deadline'           => 'date',
        'deliverydate'       => 'date',
        'enddate'            => 'date',
        'description'        => 'string',
        'internalnote'       => 'string',
        'tags'               => 'array',
        'archivedon'         => 'date',
        'customfields'       => 'customfields',
        'budget'             => 'float',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    const RELATIONS = [
        'phase' => ProjectPhase::class,
        'tags'  => Tag::class,
    ];

    protected static function entity(): string
    {
        return 'umbrellaproject';
    }
}
