<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Employee resource.
 *
 * @property-read string $createdon            Created timestamp.
 * @property-read string $updatedon            Updated timestamp.
 * @property-read int    $id                   Unique identifier.
 * @property-read string $searchname           Search name.
 * @property-read bool   $active               Active status.
 * @property      string $customfields         Custom fields.
 * @property      int    $userphoto            FK → File.
 * @property      string $title                Title.
 * @property      string $screenname           Screen name.
 * @property      int    $number               Employee number.
 * @property      string $dateofbirth          Date of birth.
 * @property      string $socialsecuritynumber Social security number.
 * @property      string $emailprivate         Private email.
 * @property      string $bankaccount          Bank account (IBAN).
 * @property      string $bankcity             Bank city.
 * @property      string $bankascription       Bank ascription.
 * @property      string $notes                Notes.
 * @property      string $employeesince        Employee since date.
 * @property      string $username             Username (required).
 * @property      string $password             Password (required).
 * @property      int    $role                 FK → Role (required). Settings > rechtenprofielen.
 * @property      string $email                Email.
 * @property      string $phone                Phone number.
 * @property      string $mobile               Mobile number.
 * @property      string $street               Street.
 * @property      string $adresline2           Address line 2.
 * @property      string $streetnumber         Street number.
 * @property      string $zipcode              Zip code.
 * @property      string $city                 City.
 * @property      string $country              Country.
 * @property      string $function             Function/role.
 * @property      string $salutation           Salutation: SIR | MADAM | SIRMADAM.
 * @property      string $initials             Initials.
 * @property      string $firstname            First name.
 * @property      string $infix                Infix.
 * @property      string $lastname             Last name.
 * @property      int    $department           FK → Department.
 * @property      int    $identity             FK → Identity.
 * @property      string $extendedproperties   Extended properties.
 * @property      array  $tags                 FK[] → Tag.
 * @property      array  $skills               FK[] → TaskType.
 */
class Employee extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'            => 'datetime',
        'updatedon'            => 'datetime',
        'id'                   => 'int',
        'customfields'         => 'customfields',
        'searchname'           => 'string',
        'userphoto'            => 'int',
        'title'                => 'string',
        'screenname'           => 'string',
        'number'               => 'int',
        'dateofbirth'          => 'date',
        'socialsecuritynumber' => 'string',
        'emailprivate'         => 'string',
        'bankaccount'          => 'string',
        'bankcity'             => 'string',
        'bankascription'       => 'string',
        'notes'                => 'string',
        'employeesince'        => 'date',
        'username'             => 'string',
        'password'             => 'string',
        'active'               => 'boolean',
        'role'                 => 'int',
        'email'                => 'string',
        'phone'                => 'string',
        'mobile'               => 'string',
        'street'               => 'string',
        'adresline2'           => 'string',
        'streetnumber'         => 'string',
        'zipcode'              => 'string',
        'city'                 => 'string',
        'country'              => 'string',
        'function'             => 'string',
        'salutation'           => 'string',
        'initials'             => 'string',
        'firstname'            => 'string',
        'infix'                => 'string',
        'lastname'             => 'string',
        'department'           => 'int',
        'identity'             => 'int',
        'extendedproperties'   => 'string',
        'tags'                 => 'array',
        'skills'               => 'array',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'active',
    ];

    const REQUIRED = [
        'username',
        'password',
        'role',
    ];

    const RELATIONS = [
        'userphoto'  => File::class,
        'department' => Department::class,
        'tags'       => Tag::class,
        'skills'     => TaskType::class,
    ];

    protected static function entity(): string
    {
        return 'employee';
    }

    /**
     * Get working hours for employees within a date range.
     *
     * @param  array  $employeeIds    Array of employee IDs.
     * @param  string $startDate      Start date (yyyy-mm-dd).
     * @param  string $stopDate       Stop date (yyyy-mm-dd).
     * @param  bool   $includeAbsence Whether to include absence hours.
     * @return array  Sum of working hours, and working hours grouped by day.
     */
    public static function getWorkingHours(array $employeeIds, string $startDate, string $stopDate, bool $includeAbsence = false): array
    {
        return static::rpcCall('getWorkingHours', [$employeeIds, $startDate, $stopDate, $includeAbsence])->result();
    }
}
