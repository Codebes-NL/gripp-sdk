<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Purchaseinvoice resource.
 *
 * @property-read string $createdon                      Created timestamp.
 * @property-read string $updatedon                      Updated timestamp.
 * @property-read int    $id                             Unique identifier.
 * @property      string $customfields                   Custom fields.
 * @property-read string $searchname                     Search name.
 * @property      int    $company                        FK → Company (required).
 * @property      int    $contact                        FK → Contact.
 * @property      string $number                         Number (required).
 * @property      string $subject                        Subject.
 * @property      string $date                           Date.
 * @property      string $reportdate                     Report date.
 * @property      string $expirydate                     Expiry date.
 * @property      string $phase                          Phase (required): CONCEPT | CHECK | AGREED | PROCESSED | ONHOLD.
 * @property      string $paymentmethod                  Payment method (required): MANUALTRANSFER | AUTOMATICTRANSFER.
 * @property-read float  $totalinclvat                   Total incl. VAT.
 * @property-read float  $totalpayed                     Total payed.
 * @property      int    $bookingnumber                  Booking number.
 * @property      int    $identity                       FK → Identity (required). Settings > Identiteiten & Sjablonen.
 * @property      string $description                    Description.
 * @property-read float  $totalincldiscountinclvat       Total incl. discount incl. VAT.
 * @property-read float  $totalopeninclvat               Total open incl. VAT.
 * @property      string $extendedproperties             Extended properties.
 * @property      array  $tags                           FK[] → Tag.
 * @property      array  $employees                      FK[] → Employee.
 * @property      array  $employees_starred              FK[] → Employee.
 * @property      array  $files                          FK[] → File.
 * @property-read float  $totalbuyingincldiscountexclvat Total buying incl. discount excl. VAT.
 * @property      array  $purchaseinvoicelines           FK[] → PurchaseInvoiceLine.
 * @property      array  $payments                       FK[] → PurchasePayment.
 * @property      bool   $isopenstaand                   Is openstaand.
 * @property      bool   $unprocessed                    Allows a purchase invoice to be set to processed or unprocessed.
 */
class PurchaseInvoice extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'                      => 'datetime',
        'updatedon'                      => 'datetime',
        'id'                             => 'int',
        'customfields'                   => 'customfields',
        'searchname'                     => 'string',
        'company'                        => 'int',
        'contact'                        => 'int',
        'number'                         => 'string',
        'subject'                        => 'string',
        'date'                           => 'date',
        'reportdate'                     => 'date',
        'expirydate'                     => 'date',
        'phase'                          => 'string',
        'paymentmethod'                  => 'string',
        'totalinclvat'                   => 'float',
        'totalpayed'                     => 'float',
        'bookingnumber'                  => 'int',
        'identity'                       => 'int',
        'description'                    => 'string',
        'totalincldiscountinclvat'       => 'float',
        'totalopeninclvat'               => 'float',
        'extendedproperties'             => 'string',
        'tags'                           => 'array',
        'employees'                      => 'array',
        'employees_starred'              => 'array',
        'files'                          => 'array',
        'totalbuyingincldiscountexclvat' => 'float',
        'purchaseinvoicelines'           => 'array',
        'payments'                       => 'array',
        'isopenstaand'                   => 'boolean',
        'unprocessed'                    => 'boolean',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'totalinclvat',
        'totalpayed',
        'totalincldiscountinclvat',
        'totalopeninclvat',
        'totalbuyingincldiscountexclvat',
    ];

    public const REQUIRED = [
        'company',
        'number',
        'phase',
        'paymentmethod',
        'identity',
    ];

    public const RELATIONS = [
        'company'              => Company::class,
        'contact'              => Contact::class,
        'tags'                 => Tag::class,
        'employees'            => Employee::class,
        'employees_starred'    => Employee::class,
        'files'                => File::class,
        'purchaseinvoicelines' => PurchaseInvoiceLine::class,
        'payments'             => PurchasePayment::class,
    ];

    protected static function entity(): string
    {
        return 'purchaseinvoice';
    }
}
