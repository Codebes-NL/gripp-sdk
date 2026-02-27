<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Calendaritem resource.
 *
 * @property-read string $createdon             Created timestamp.
 * @property-read string $updatedon             Updated timestamp.
 * @property-read int    $id                    Unique identifier.
 * @property-read string $searchname            Search name.
 * @property      int    $_ordering             Ordering.
 * @property      string $subject               Subject.
 * @property      string $date                  Date (required).
 * @property      float  $hours                 Hours (required).
 * @property      int    $calendaritememployee   FK → Employee.
 * @property      string $completedon           Completed on date.
 * @property      string $time                  Time.
 * @property      int    $timelineentry         FK → TimelineEntry.
 * @property      int    $task                  FK → Task (required).
 * @property      string $extendedproperties    Extended properties.
 */
class CalendarItem extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'            => 'datetime',
        'updatedon'            => 'datetime',
        '_ordering'            => 'int',
        'id'                   => 'int',
        'searchname'           => 'string',
        'subject'              => 'string',
        'date'                 => 'date',
        'hours'                => 'float',
        'calendaritememployee' => 'int',
        'completedon'          => 'date',
        'time'                 => 'time',
        'timelineentry'        => 'int',
        'task'                 => 'int',
        'extendedproperties'   => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    const REQUIRED = [
        'date',
        'hours',
        'task',
    ];

    const RELATIONS = [
        'calendaritememployee' => Employee::class,
        'timelineentry'        => TimelineEntry::class,
        'task'                 => Task::class,
    ];

    protected static function entity(): string
    {
        return 'calendaritem';
    }
}
