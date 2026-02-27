<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Employmentcontract resource.
 *
 * @property-read string $createdon            Created timestamp.
 * @property-read string $updatedon            Updated timestamp.
 * @property-read int    $id                   Unique identifier.
 * @property-read string $timeslots_odd_weeks  Timeslots odd weeks (JSON).
 * @property-read string $timeslots_even_weeks Timeslots even weeks (JSON).
 * @property      string $startdate            Start date (required).
 * @property      string $enddate              End date (required).
 * @property      int    $employee             FK → Employee.
 * @property      float  $hours_monday_odd     Hours Monday (odd weeks).
 * @property      float  $hours_tuesday_odd    Hours Tuesday (odd weeks).
 * @property      float  $hours_wednesday_odd  Hours Wednesday (odd weeks).
 * @property      float  $hours_thursday_odd   Hours Thursday (odd weeks).
 * @property      float  $hours_friday_odd     Hours Friday (odd weeks).
 * @property      float  $hours_saturday_odd   Hours Saturday (odd weeks).
 * @property      float  $hours_sunday_odd     Hours Sunday (odd weeks).
 * @property      float  $hours_monday_even    Hours Monday (even weeks).
 * @property      float  $hours_tuesday_even   Hours Tuesday (even weeks).
 * @property      float  $hours_wednesday_even Hours Wednesday (even weeks).
 * @property      float  $hours_thursday_even  Hours Thursday (even weeks).
 * @property      float  $hours_friday_even    Hours Friday (even weeks).
 * @property      float  $hours_saturday_even  Hours Saturday (even weeks).
 * @property      float  $hours_sunday_even    Hours Sunday (even weeks).
 * @property      float  $internal_price_per_hour Internal price per hour.
 */
class EmploymentContract extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'            => 'datetime',
        'updatedon'            => 'datetime',
        'id'                   => 'int',
        'startdate'            => 'date',
        'enddate'              => 'date',
        'employee'             => 'int',
        'hours_monday_odd'     => 'float',
        'hours_tuesday_odd'    => 'float',
        'hours_wednesday_odd'  => 'float',
        'hours_thursday_odd'   => 'float',
        'hours_friday_odd'     => 'float',
        'hours_saturday_odd'   => 'float',
        'hours_sunday_odd'     => 'float',
        'hours_monday_even'    => 'float',
        'hours_tuesday_even'   => 'float',
        'hours_wednesday_even' => 'float',
        'hours_thursday_even'  => 'float',
        'hours_friday_even'    => 'float',
        'hours_saturday_even'  => 'float',
        'hours_sunday_even'    => 'float',
        'internal_price_per_hour' => 'float',
        'timeslots_odd_weeks'  => 'json',
        'timeslots_even_weeks' => 'json',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'timeslots_odd_weeks',
        'timeslots_even_weeks',
    ];

    const REQUIRED = [
        'startdate',
        'enddate',
    ];

    const RELATIONS = [
        'employee' => Employee::class,
    ];

    protected static function entity(): string
    {
        return 'employmentcontract';
    }
}
