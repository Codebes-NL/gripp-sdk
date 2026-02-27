<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Purchaseorder resource.
 *
 * @property-read string $createdon              Created timestamp.
 * @property-read string $updatedon              Updated timestamp.
 * @property-read int    $id                     Unique identifier.
 * @property      string $customfields           Custom fields.
 * @property      int    $templateset            FK → Template set. Settings > Identiteiten & Sjablonen > Sjabloonsets.
 * @property      bool   $filesavailableforsupplier Files available for supplier.
 * @property-read string $searchname             Search name.
 * @property      string $name                   Name.
 * @property      int    $number                 Number.
 * @property      int    $phase                  FK → Inkoopopdrachtfase.
 * @property      string $deadline               Deadline.
 * @property      int    $company                FK → Company.
 * @property      int    $contact                FK → Contact.
 * @property      string $date                   Date.
 * @property      string $description            Description (textmarkdown).
 * @property      string $workdeliveraddress     Work/deliver address.
 * @property      string $clientreference        Client reference.
 * @property      bool   $archived               Archived.
 * @property      string $archivedon             Archived on date.
 * @property      string $extendedproperties     Extended properties.
 * @property      array  $tags                   FK[] → Tag.
 * @property      array  $employees              FK[] → Employee.
 * @property      array  $employees_starred      FK[] → Employee.
 * @property      string $purchaseorderstatus    Purchase order status: CONCEPT | SENT | COMPLETED.
 * @property      float  $totalincldiscountexclvat Total incl. discount excl. VAT.
 * @property      int    $supplier               FK → User (company or contact).
 * @property      int    $extrapdf1              FK → File.
 * @property      int    $extrapdf2              FK → File.
 * @property      array  $files                  FK[] → File.
 * @property      int    $accountmanager         FK → Employee.
 * @property      array  $purchaseorderlines     FK[] → PurchaseOrderLine.
 *
 * @deprecated property $identity Replaced by templateset.
 */
class PurchaseOrder extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'                => 'datetime',
        'updatedon'                => 'datetime',
        'id'                       => 'int',
        'customfields'             => 'customfields',
        'templateset'              => 'int',
        'filesavailableforsupplier' => 'boolean',
        'searchname'               => 'string',
        'name'                     => 'string',
        'number'                   => 'int',
        'phase'                    => 'int',
        'deadline'                 => 'date',
        'company'                  => 'int',
        'contact'                  => 'int',
        'date'                     => 'date',
        'description'              => 'string',
        'workdeliveraddress'       => 'string',
        'clientreference'          => 'string',
        'archived'                 => 'boolean',
        'archivedon'               => 'date',
        'extendedproperties'       => 'string',
        'tags'                     => 'array',
        'employees'                => 'array',
        'employees_starred'        => 'array',
        'purchaseorderstatus'      => 'string',
        'totalincldiscountexclvat' => 'float',
        'supplier'                 => 'int',
        'extrapdf1'                => 'int',
        'extrapdf2'                => 'int',
        'files'                    => 'array',
        'accountmanager'           => 'int',
        'purchaseorderlines'       => 'array',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
    ];

    public const RELATIONS = [
        'company'            => Company::class,
        'contact'            => Contact::class,
        'tags'               => Tag::class,
        'employees'          => Employee::class,
        'employees_starred'  => Employee::class,
        'extrapdf1'          => File::class,
        'extrapdf2'          => File::class,
        'files'              => File::class,
        'accountmanager'     => Employee::class,
        'purchaseorderlines' => PurchaseOrderLine::class,
    ];

    protected static function entity(): string
    {
        return 'purchaseorder';
    }
}
