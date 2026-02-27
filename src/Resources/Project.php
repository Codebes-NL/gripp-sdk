<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Project resource.
 *
 * Represents projects linked to companies. Supports full CRUD.
 *
 * @example
 * // Find a project
 * $project = Project::find(789);
 *
 * // Query active projects for a company
 * $projects = Project::where('company', 42)
 *     ->where('archived', false)
 *     ->orderBy('createdon', 'desc')
 *     ->get();
 *
 * // Create a project (templateset, name, company are required)
 * $result = Project::create([
 *     'templateset' => 1,
 *     'name' => 'Website Redesign',
 *     'company' => 42,
 *     'description' => 'Full redesign of corporate website.',
 *     'startdate' => '2025-03-01',
 * ]);
 *
 * // Update
 * Project::update(789, ['deadline' => '2025-06-30']);
 *
 * @property-read string $createdon               Created timestamp.
 * @property-read string $updatedon               Updated timestamp.
 * @property-read int    $id                      Unique identifier.
 * @property      string $customfields            Custom fields.
 * @property      int    $templateset             FK → Template set (required). Settings > Identiteiten & Sjablonen > Sjabloonsets.
 * @property-read string $searchname              Search name.
 * @property      string $name                    Name (required).
 * @property      string $color                   Color.
 * @property      int    $validfor                FK → Offer condition (offertegeldigheid).
 * @property      int    $accountmanager          FK → Employee.
 * @property      bool   $filesavailableforclient Files available for client.
 * @property-read int    $number                  Number.
 * @property      int    $phase                   FK → ProjectPhase.
 * @property      string $deadline                Deadline.
 * @property      int    $company                 FK → Company (required).
 * @property      int    $contact                 FK → Contact.
 * @property      string $startdate               Start date.
 * @property      string $deliverydate            Delivery date.
 * @property      string $enddate                 End date.
 * @property      bool   $addhoursspecification   Add hours specification.
 * @property      string $description             Description.
 * @property      string $workdeliveraddress      Work/deliver address.
 * @property      string $clientreference         Client reference.
 * @property-read bool   $isbasis                 Is basis.
 * @property-read float  $totalinclvat            Total incl. VAT.
 * @property-read float  $totalexclvat            Total excl. VAT.
 * @property      bool   $archived                Archived.
 * @property      string $archivedon              Archived on date.
 * @property      string $extendedproperties      Extended properties.
 * @property      array  $tags                    FK[] → Tag.
 * @property      array  $employees               FK[] → Employee.
 * @property      array  $employees_starred       FK[] → Employee.
 * @property      int    $extrapdf1               FK → File.
 * @property      int    $extrapdf2               FK → File.
 * @property      array  $files                   FK[] → File.
 * @property      array  $projectlines            FK[] → OfferProjectLine.
 * @property      int    $umbrellaproject         FK → UmbrellaProject.
 *
 * @deprecated property $identity Replaced by templateset.
 */
class Project extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'               => 'datetime',
        'updatedon'               => 'datetime',
        'id'                      => 'int',
        'customfields'            => 'customfields',
        'templateset'             => 'int',
        'searchname'              => 'string',
        'name'                    => 'string',
        'color'                   => 'color',
        'validfor'                => 'int',
        'accountmanager'          => 'int',
        'filesavailableforclient' => 'boolean',
        'number'                  => 'int',
        'phase'                   => 'int',
        'deadline'                => 'date',
        'company'                 => 'int',
        'contact'                 => 'int',
        'startdate'               => 'date',
        'deliverydate'            => 'date',
        'enddate'                 => 'date',
        'addhoursspecification'   => 'boolean',
        'description'             => 'string',
        'workdeliveraddress'      => 'string',
        'clientreference'         => 'string',
        'isbasis'                 => 'boolean',
        'totalinclvat'            => 'float',
        'totalexclvat'            => 'float',
        'archived'                => 'boolean',
        'archivedon'              => 'date',
        'extendedproperties'      => 'string',
        'tags'                    => 'array',
        'employees'               => 'array',
        'employees_starred'       => 'array',
        'extrapdf1'               => 'int',
        'extrapdf2'               => 'int',
        'files'                   => 'array',
        'projectlines'            => 'array',
        'umbrellaproject'         => 'int',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'number',
        'isbasis',
        'totalinclvat',
        'totalexclvat',
    ];

    const REQUIRED = [
        'templateset',
        'name',
        'company',
    ];

    const RELATIONS = [
        'accountmanager'   => Employee::class,
        'phase'            => ProjectPhase::class,
        'company'          => Company::class,
        'contact'          => Contact::class,
        'tags'             => Tag::class,
        'employees'        => Employee::class,
        'employees_starred' => Employee::class,
        'extrapdf1'        => File::class,
        'extrapdf2'        => File::class,
        'files'            => File::class,
        'projectlines'     => OfferProjectLine::class,
        'umbrellaproject'  => UmbrellaProject::class,
    ];

    protected static function entity(): string
    {
        return 'project';
    }
}
