<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Contract resource.
 *
 * @property-read string $createdon              Created timestamp.
 * @property-read string $updatedon              Updated timestamp.
 * @property-read int    $id                     Unique identifier.
 * @property-read string $searchname             Search name.
 * @property-read string $expirydate             Expiry date.
 * @property-read int    $number                 Contract number.
 * @property-read string $status                 Status: ACTIVE | ENDING | ENDED.
 * @property-read bool   $isbasis                Is basis contract.
 * @property      string $customfields           Custom fields.
 * @property      int    $templateset            FK → Template set (required). Settings > Identities & Templates > Template sets.
 * @property      string $name                   Name (required).
 * @property      string $date                   Date (required).
 * @property      string $date_original          Original date.
 * @property      bool   $filesavailableforclient Files available for client.
 * @property      int    $company                FK → Company (required).
 * @property      int    $contact                FK → Contact.
 * @property      string $subject                Subject.
 * @property      string $subject_invoice        Invoice subject.
 * @property      string $frequency              Frequency (required): EVERYMONTH | EVERYQUARTER | EVERY6MONTHS | EVERYYEAR | EVERY18MONTHS | EVERYTWOYEARS | EVERYWEEK | EVERYTWOWEEKS | EVERYTHREEYEARS | EVERYFOURYEARS | EVERYFIVEYEARS | EVERYTHREEWEEKS | EVERYFOURWEEKS | EVERY2MONTHS | EVERY4MONTHS.
 * @property      bool   $sendmaxtimescheckbox   Send max times checkbox.
 * @property      int    $sendmaxtimes           Send max times.
 * @property      bool   $extendautomatically    Extend automatically.
 * @property      int    $extendperiod           Extend period.
 * @property      string $paymentmethod          Payment method (required): MANUALTRANSFER | AUTOMATICTRANSFER.
 * @property      string $clientreference        Client reference.
 * @property      string $description            Description.
 * @property      string $extendedproperties     Extended properties.
 * @property      int    $validfor               FK → Offer condition (Settings > Offers > Conditions).
 * @property      array  $tags                   FK[] → Tag.
 * @property      array  $employees              FK[] → Employee.
 * @property      array  $employees_starred      FK[] → Employee (starred).
 * @property      int    $extrapdf1              FK → File.
 * @property      int    $extrapdf2              FK → File.
 * @property      array  $files                  FK[] → File.
 * @property      array  $contractlines          FK[] → ContractLine.
 */
class Contract extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'              => 'datetime',
        'updatedon'              => 'datetime',
        'id'                     => 'int',
        'customfields'           => 'customfields',
        'templateset'            => 'int',
        'searchname'             => 'string',
        'name'                   => 'string',
        'date'                   => 'date',
        'date_original'          => 'date',
        'expirydate'             => 'date',
        'number'                 => 'int',
        'filesavailableforclient' => 'boolean',
        'status'                 => 'string',
        'company'                => 'int',
        'contact'                => 'int',
        'subject'                => 'string',
        'subject_invoice'        => 'string',
        'frequency'              => 'string',
        'sendmaxtimescheckbox'   => 'boolean',
        'sendmaxtimes'           => 'int',
        'extendautomatically'    => 'boolean',
        'extendperiod'           => 'int',
        'paymentmethod'          => 'string',
        'clientreference'        => 'string',
        'description'            => 'string',
        'isbasis'                => 'boolean',
        'extendedproperties'     => 'string',
        'validfor'               => 'int',
        'tags'                   => 'array',
        'employees'              => 'array',
        'employees_starred'      => 'array',
        'extrapdf1'              => 'int',
        'extrapdf2'              => 'int',
        'files'                  => 'array',
        'contractlines'          => 'array',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'expirydate',
        'number',
        'status',
        'isbasis',
    ];

    public const REQUIRED = [
        'templateset',
        'name',
        'date',
        'company',
        'frequency',
        'paymentmethod',
    ];

    public const RELATIONS = [
        'company'           => Company::class,
        'contact'           => Contact::class,
        'tags'              => Tag::class,
        'employees'         => Employee::class,
        'employees_starred' => Employee::class,
        'extrapdf1'         => File::class,
        'extrapdf2'         => File::class,
        'files'             => File::class,
        'contractlines'     => ContractLine::class,
    ];

    protected static function entity(): string
    {
        return 'contract';
    }
}
