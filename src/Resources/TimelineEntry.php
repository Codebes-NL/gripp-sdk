<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Timelineentry resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property-read string $searchname         Search name.
 * @property      string $subject            Subject.
 * @property      int    $employee           FK → Employee.
 * @property-read string $date               Date.
 * @property      string $message            Message.
 * @property-read string $timelinetype       Timeline type: USERMESSAGE | SYSTEMMESSAGE | SENTEMAIL | RECEIVEDEMAIL | SIGNEDOFFER | DECLINEDOFFER | SIGNEDTASK | DECLINEDTASK | SIGNEDOPDRACHT | DECLINEDOPDRACHT | SENTFORACCEPTANCE | SIGNEDINKOOPOPDRACHT | DECLINEDINKOOPOPDRACHT.
 * @property      int    $appointmenttype    FK → Appointment type. Settings > Divers > Afspraaktype.
 * @property      int    $company            FK → Company (required).
 * @property      int    $contact            FK → Contact.
 * @property      int    $offer              FK → Offer.
 * @property      int    $project            FK → Project.
 * @property      int    $invoice            FK → Invoice.
 * @property      int    $purchaseinvoice    FK → PurchaseInvoice.
 * @property      int    $purchaseorder      FK → PurchaseOrder.
 * @property      int    $contract           FK → Contract.
 * @property      int    $task               FK → Task.
 * @property      bool   $starred            Starred.
 * @property      string $extendedproperties Extended properties.
 * @property      array  $files              FK[] → File.
 * @property      bool   $showinplanning     Show in planning.
 * @property      int    $calendaritem       FK → CalendarItem.
 */
class TimelineEntry extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'searchname'         => 'string',
        'subject'            => 'string',
        'employee'           => 'int',
        'date'               => 'datetime',
        'message'            => 'string',
        'timelinetype'       => 'string',
        'appointmenttype'    => 'int',
        'company'            => 'int',
        'contact'            => 'int',
        'offer'              => 'int',
        'project'            => 'int',
        'invoice'            => 'int',
        'purchaseinvoice'    => 'int',
        'purchaseorder'      => 'int',
        'contract'           => 'int',
        'task'               => 'int',
        'starred'            => 'boolean',
        'extendedproperties' => 'string',
        'files'              => 'array',
        'showinplanning'     => 'boolean',
        'calendaritem'       => 'int',
    ];

    public const READONLY = [
        'createdon',
        'updatedon',
        'id',
        'searchname',
        'date',
        'timelinetype',
    ];

    public const REQUIRED = [
        'company',
    ];

    public const RELATIONS = [
        'employee'        => Employee::class,
        'company'         => Company::class,
        'contact'         => Contact::class,
        'offer'           => Offer::class,
        'project'         => Project::class,
        'invoice'         => Invoice::class,
        'purchaseinvoice' => PurchaseInvoice::class,
        'purchaseorder'   => PurchaseOrder::class,
        'contract'        => Contract::class,
        'task'            => Task::class,
        'files'           => File::class,
        'calendaritem'    => CalendarItem::class,
    ];

    protected static function entity(): string
    {
        return 'timelineentry';
    }
}
