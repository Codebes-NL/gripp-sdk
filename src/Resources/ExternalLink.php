<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\Resources\Concerns\CanCreate;
use CodeBes\GrippSdk\Resources\Concerns\CanDelete;
use CodeBes\GrippSdk\Resources\Concerns\CanRead;
use CodeBes\GrippSdk\Resources\Concerns\CanUpdate;

/**
 * Externallink resource.
 *
 * @property int    $record_id Record ID.
 * @property string $page      Page: COMPANY | CONTRACT | EMPLOYEE | INVOICE | OFFER | PROJECT | PURCHASEINVOICE | PURCHASEORDER.
 * @property string $label     Label.
 * @property string $link      Full link, including http:// or https://.
 * @property int    $icon_id   Icon ID.
 * @property string $color     Color in HEX format, including #.
 */
class ExternalLink extends Resource
{
    use CanCreate;
    use CanRead;
    use CanUpdate;
    use CanDelete;

    public const FIELDS = [
        'record_id' => 'int',
        'page'      => 'string',
        'label'     => 'string',
        'link'      => 'string',
        'icon_id'   => 'int',
        'color'     => 'color',
    ];

    protected static function entity(): string
    {
        return 'externallink';
    }
}
