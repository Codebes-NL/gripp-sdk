<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Offer resource.
 *
 * @property-read string $createdon                  Created timestamp.
 * @property-read string $updatedon                  Updated timestamp.
 * @property-read int    $id                         Unique identifier.
 * @property      string $customfields               Custom fields.
 * @property      int    $template                   FK → Template set (required). Settings > Identiteiten & Sjablonen > Sjabloonsets.
 * @property-read string $searchname                 Search name.
 * @property      string $name                       Name (required).
 * @property      string $date                       Date.
 * @property      int    $validfor                   FK → Offer condition. Settings > Offertes > Condities.
 * @property      string $deadline                   Deadline.
 * @property-read int    $number                     Number.
 * @property      int    $phase                      FK → OfferPhase.
 * @property      string $acceptancestatus           Acceptance status: ACCEPTED | REJECTED.
 * @property      string $acceptedon                 Accepted on date.
 * @property-read bool   $isbasis                    Is basis.
 * @property-read float  $totalinclvat               Total incl. VAT.
 * @property      float  $totalexclvat               Total excl. VAT.
 * @property      bool   $signingenabled             Signing enabled.
 * @property      bool   $filesavailableforclient    Files available for client.
 * @property      int    $company                    FK → Company (required).
 * @property      int    $contact                    FK → Contact.
 * @property      int    $chance                     Chance percentage.
 * @property      string $description                Description.
 * @property      string $workdeliveraddress         Work/deliver address.
 * @property      string $subject                    Subject.
 * @property      int    $accountmanager             FK → Employee.
 * @property      string $salesdistributiondatestart Sales distribution date start.
 * @property      string $salesdistributiondatestop  Sales distribution date stop.
 * @property      int    $salesassociate             FK → Employee.
 * @property      string $status                     Status: CONCEPT | SENT.
 * @property      string $clientreference            Client reference.
 * @property      bool   $archived                   Archived.
 * @property      string $archivedon                 Archived on date.
 * @property      string $extendedproperties         Extended properties.
 * @property      int    $extrapdf1                  FK → File.
 * @property      int    $extrapdf2                  FK → File.
 * @property      array  $files                      FK[] → File.
 * @property      string $frequency                  Frequency: EVERYMONTH | EVERYQUARTER | EVERY6MONTHS | EVERYYEAR | EVERY18MONTHS | EVERYTWOYEARS | EVERYWEEK | EVERYTWOWEEKS | EVERYTHREEYEARS | EVERYFOURYEARS | EVERYFIVEYEARS | EVERYTHREEWEEKS | EVERYFOURWEEKS | EVERY2MONTHS | EVERY4MONTHS.
 * @property      int    $expectedterms              Expected terms.
 * @property      bool   $sendmaxtimescheckbox       Send max times checkbox.
 * @property      int    $sendmaxtimes               Send max times.
 * @property      bool   $renewautomatically         Renew automatically.
 * @property      int    $renewperiods               Renew periods.
 * @property      string $paymentmethod              Payment method: MANUALTRANSFER | AUTOMATICTRANSFER.
 * @property      array  $tags                       FK[] → Tag.
 * @property      array  $employees                  FK[] → Employee.
 * @property      array  $employees_starred          FK[] → Employee.
 * @property      array  $offerlines                 FK[] → OfferProjectLine.
 * @property      int    $umbrellaproject            FK → UmbrellaProject.
 * @property      bool   $isopportunity              Is opportunity.
 *
 * @deprecated property $validity   Replaced by validfor.
 * @deprecated property $identity   Replaced by template.
 */
class Offer extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'                  => 'datetime',
        'updatedon'                  => 'datetime',
        'id'                         => 'int',
        'customfields'               => 'customfields',
        'template'                   => 'int',
        'searchname'                 => 'string',
        'name'                       => 'string',
        'date'                       => 'date',
        'validfor'                   => 'int',
        'deadline'                   => 'date',
        'number'                     => 'int',
        'phase'                      => 'int',
        'acceptancestatus'           => 'string',
        'acceptedon'                 => 'date',
        'isbasis'                    => 'boolean',
        'totalinclvat'               => 'float',
        'totalexclvat'               => 'float',
        'signingenabled'             => 'boolean',
        'filesavailableforclient'    => 'boolean',
        'company'                    => 'int',
        'contact'                    => 'int',
        'chance'                     => 'int',
        'description'                => 'string',
        'workdeliveraddress'         => 'string',
        'subject'                    => 'string',
        'accountmanager'             => 'int',
        'salesdistributiondatestart' => 'date',
        'salesdistributiondatestop'  => 'date',
        'salesassociate'             => 'int',
        'status'                     => 'string',
        'clientreference'            => 'string',
        'archived'                   => 'boolean',
        'archivedon'                 => 'date',
        'extendedproperties'         => 'string',
        'extrapdf1'                  => 'int',
        'extrapdf2'                  => 'int',
        'files'                      => 'array',
        'frequency'                  => 'string',
        'expectedterms'              => 'int',
        'sendmaxtimescheckbox'       => 'boolean',
        'sendmaxtimes'               => 'int',
        'renewautomatically'         => 'boolean',
        'renewperiods'               => 'int',
        'paymentmethod'              => 'string',
        'tags'                       => 'array',
        'employees'                  => 'array',
        'employees_starred'          => 'array',
        'offerlines'                 => 'array',
        'umbrellaproject'            => 'int',
        'isopportunity'              => 'boolean',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'number',
        'isbasis',
        'totalinclvat',
    ];

    const REQUIRED = [
        'template',
        'name',
        'company',
    ];

    const RELATIONS = [
        'phase'            => OfferPhase::class,
        'company'          => Company::class,
        'contact'          => Contact::class,
        'accountmanager'   => Employee::class,
        'salesassociate'   => Employee::class,
        'extrapdf1'        => File::class,
        'extrapdf2'        => File::class,
        'files'            => File::class,
        'tags'             => Tag::class,
        'employees'        => Employee::class,
        'employees_starred' => Employee::class,
        'offerlines'       => OfferProjectLine::class,
        'umbrellaproject'  => UmbrellaProject::class,
    ];

    protected static function entity(): string
    {
        return 'offer';
    }
}
