<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Invoice resource.
 *
 * @property-read string $createdon                      Created timestamp.
 * @property-read string $updatedon                      Updated timestamp.
 * @property-read int    $id                             Unique identifier.
 * @property-read string $searchname                     Search name.
 * @property-read float  $totalpayed                     Total payed.
 * @property-read bool   $isbasis                        Is basis invoice.
 * @property-read string $expirydate                     Expiry date.
 * @property-read float  $totalincldiscountexclvat       Total incl. discount excl. VAT.
 * @property-read float  $totalopeninclvat               Total open incl. VAT.
 * @property-read float  $totalincldiscountinclvat       Total incl. discount incl. VAT.
 * @property-read float  $totalbuyingincldiscountexclvat Total buying incl. discount excl. VAT.
 * @property-read float  $totalinclvat                   Total incl. VAT.
 * @property-read mixed  $fase                           Phase (subquery).
 * @property-read string $viewonlineurl                  URL to online viewer for document acceptance.
 * @property-read string $directpdfurl                   URL to the PDF document.
 * @property      string $customfields                   Custom fields.
 * @property      bool   $filesavailableforclient        Files available for client.
 * @property      int    $templateset                    FK → Template set (required). Settings > Identities & Templates > Template sets.
 * @property      int    $validfor                       FK → Offer condition.
 * @property      string $status                         Status (required): CONCEPT | SENT.
 * @property      int    $number                         Invoice number.
 * @property      string $date                           Date.
 * @property      string $reportdate                     Report date.
 * @property      string $subject                        Subject.
 * @property      string $paymentmethod                  Payment method (required): MANUALTRANSFER | AUTOMATICTRANSFER.
 * @property      int    $company                        FK → Company (required).
 * @property      int    $client                         FK → User (required).
 * @property      int    $contact                        FK → Contact.
 * @property      string $clientreference                Client reference.
 * @property      bool   $addhoursspecification          Add hours specification.
 * @property      string $description                    Description.
 * @property      string $extendedproperties             Extended properties.
 * @property      array  $tags                           FK[] → Tag.
 * @property      int    $extrapdf1                      FK → File.
 * @property      int    $extrapdf2                      FK → File.
 * @property      array  $files                          FK[] → File.
 * @property      array  $invoicelines                   FK[] → InvoiceLine.
 * @property      array  $payments                       FK[] → Payment.
 */
class Invoice extends Resource
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
        'filesavailableforclient'        => 'boolean',
        'templateset'                    => 'int',
        'validfor'                       => 'int',
        'searchname'                     => 'string',
        'status'                         => 'string',
        'number'                         => 'int',
        'date'                           => 'date',
        'reportdate'                     => 'date',
        'subject'                        => 'string',
        'totalpayed'                     => 'float',
        'paymentmethod'                  => 'string',
        'company'                        => 'int',
        'client'                         => 'int',
        'contact'                        => 'int',
        'clientreference'                => 'string',
        'addhoursspecification'          => 'boolean',
        'description'                    => 'string',
        'isbasis'                        => 'boolean',
        'expirydate'                     => 'date',
        'totalincldiscountexclvat'       => 'float',
        'totalopeninclvat'               => 'float',
        'totalincldiscountinclvat'       => 'float',
        'extendedproperties'             => 'string',
        'tags'                           => 'array',
        'extrapdf1'                      => 'int',
        'extrapdf2'                      => 'int',
        'files'                          => 'array',
        'totalbuyingincldiscountexclvat' => 'float',
        'invoicelines'                   => 'array',
        'payments'                       => 'array',
        'totalinclvat'                   => 'float',
        'fase'                           => 'subquery',
        'viewonlineurl'                  => 'string',
        'directpdfurl'                   => 'string',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'totalpayed',
        'isbasis',
        'expirydate',
        'totalincldiscountexclvat',
        'totalopeninclvat',
        'totalincldiscountinclvat',
        'totalbuyingincldiscountexclvat',
        'totalinclvat',
        'fase',
        'viewonlineurl',
        'directpdfurl',
    ];

    public const REQUIRED = [
        'templateset',
        'status',
        'paymentmethod',
        'company',
        'client',
    ];

    public const RELATIONS = [
        'company'      => Company::class,
        'contact'      => Contact::class,
        'tags'         => Tag::class,
        'extrapdf1'    => File::class,
        'extrapdf2'    => File::class,
        'files'        => File::class,
        'invoicelines' => InvoiceLine::class,
        'payments'     => Payment::class,
    ];

    protected static function entity(): string
    {
        return 'invoice';
    }

    /**
     * Get the online viewing URL for an invoice.
     *
     * @param  int $id      The invoice ID.
     * @param  int $phaseId The invoice phase ID (Settings > Factuurfases).
     * @return array Array containing the online URL.
     */
    public static function getViewonlineUrl(int $id, int $phaseId): array
    {
        return static::rpcCall('getViewonlineUrl', [$id, $phaseId])->result();
    }

    /**
     * Mark an invoice as sent. Returns the number and date.
     *
     * @param  int $id The invoice ID.
     * @return array Array containing the number and date.
     */
    public static function markAsSent(int $id): array
    {
        return static::rpcCall('markAsSent', [$id])->result();
    }
}
