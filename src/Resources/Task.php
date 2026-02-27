<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Task resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      string $customfields       Custom fields.
 * @property      int    $type               FK → TaskType.
 * @property      string $deadlinedate       Deadline date.
 * @property      string $deadlinetime       Deadline time.
 * @property      string $description        Description.
 * @property      bool   $isafgerond         Is completed.
 * @property      string $content            Content.
 * @property-read int    $number             Number.
 * @property      int    $phase              FK → TaskPhase.
 * @property      int    $company            FK → Company (required).
 * @property      int    $offerprojectbase   FK → Offer/Project ID.
 * @property      int    $offerprojectline   FK → OfferProjectLine.
 * @property      int    $preferredemployee  FK → Employee.
 * @property      int    $packet             FK → Packet.
 * @property      float  $estimatedhours     Estimated hours.
 * @property      string $completedon        Completed on date.
 * @property      string $startdate          Start date.
 * @property      int    $base_offset_enddate Base offset end date.
 * @property      string $extendedproperties Extended properties.
 * @property-read string $checklist          Checklist (JSON).
 * @property      array  $files              FK[] → File.
 * @property      array  $calendaritems      FK[] → CalendarItem.
 */
class Task extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'searchname'         => 'string',
        'customfields'       => 'customfields',
        'type'               => 'int',
        'deadlinedate'       => 'date',
        'deadlinetime'       => 'string',
        'description'        => 'string',
        'isafgerond'         => 'boolean',
        'content'            => 'string',
        'number'             => 'int',
        'phase'              => 'int',
        'company'            => 'int',
        'offerprojectbase'   => 'int',
        'offerprojectline'   => 'int',
        'preferredemployee'  => 'int',
        'packet'             => 'int',
        'estimatedhours'     => 'float',
        'completedon'        => 'date',
        'startdate'          => 'date',
        'base_offset_enddate' => 'int',
        'extendedproperties' => 'string',
        'checklist'          => 'string',
        'files'              => 'array',
        'calendaritems'      => 'array',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'number',
        'checklist',
    ];

    const REQUIRED = [
        'company',
    ];

    const RELATIONS = [
        'type'              => TaskType::class,
        'phase'             => TaskPhase::class,
        'company'           => Company::class,
        'offerprojectline'  => OfferProjectLine::class,
        'preferredemployee' => Employee::class,
        'packet'            => Packet::class,
        'files'             => File::class,
        'calendaritems'     => CalendarItem::class,
    ];

    protected static function entity(): string
    {
        return 'task';
    }
}
