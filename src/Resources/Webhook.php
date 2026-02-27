<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Webhook resource.
 *
 * @property-read string $createdon          Created timestamp.
 * @property-read string $updatedon          Updated timestamp.
 * @property-read int    $id                 Unique identifier.
 * @property      string $webhook_trigger    Webhook trigger: NEW_COMPANY_CREATED | NEW_CONTRACT_CREATED | NEW_EMPLOYEE_CREATED | NEW_INVOICE_CREATED | NEW_OFFER_CREATED | NEW_PROJECT_CREATED | NEW_TASK_CREATED | OFFER_ACCEPTED | OFFER_DECLINED | NEW_HOUR_CREATED | INVOICE_SENT | NEW_PAYMENT | NEW_PURCHASEPAYMENT | NEW_CONTACT_CREATED | NEW_TIMELINEENTRY | TASK_DONE | NEW_PURCHASEINVOICE_CREATED | NEW_PURCHASEORDER_CREATED | PURCHASEORDER_SENT | HOUR_AUTHORIZED | HOUR_DEFINITIVE.
 * @property      string $webhook_url        Webhook URL.
 * @property      int    $errorcount         Error count.
 * @property      string $extendedproperties Extended properties.
 */
class Webhook extends Resource
{
    use CanCreate, CanRead, CanUpdate, CanDelete;

    const FIELDS = [
        'createdon'          => 'datetime',
        'updatedon'          => 'datetime',
        'id'                 => 'int',
        'webhook_trigger'    => 'string',
        'webhook_url'        => 'string',
        'errorcount'         => 'int',
        'extendedproperties' => 'string',
    ];

    const READONLY = [
        'createdon',
        'updatedon',
        'id',
    ];

    protected static function entity(): string
    {
        return 'webhook';
    }
}
