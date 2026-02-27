<?php

namespace CodeBes\GrippSdk;

use CodeBes\GrippSdk\Exceptions\GrippException;
use CodeBes\GrippSdk\Resources;
use CodeBes\GrippSdk\Transport\JsonRpcClient;

/**
 * Main entry point for the Gripp SDK.
 *
 * Call configure() once at application boot before using any resource.
 *
 * @example
 * // Configure from explicit credentials
 * GrippClient::configure('your-token', 'https://your-tenant.gripp.com');
 *
 * // Or configure from environment variables (GRIPP_API_TOKEN, GRIPP_API_URL)
 * GrippClient::configure();
 *
 * // Then use any resource
 * $company = Company::find(123);
 * $projects = Project::where('archived', false)->get();
 *
 * @see https://github.com/codebes/gripp-sdk
 */
class GrippClient
{
    /**
     * Registry of all available resources, mapped by entity name.
     *
     * @var array<string, class-string<Resources\Resource>>
     */
    const RESOURCES = [
        'absencerequest'             => Resources\AbsenceRequest::class,
        'absencerequestline'         => Resources\AbsenceRequestLine::class,
        'bulkprice'                  => Resources\BulkPrice::class,
        'calendaritem'               => Resources\CalendarItem::class,
        'company'                    => Resources\Company::class,
        'companydossier'             => Resources\CompanyDossier::class,
        'contact'                    => Resources\Contact::class,
        'contract'                   => Resources\Contract::class,
        'contractline'               => Resources\ContractLine::class,
        'cost'                       => Resources\Cost::class,
        'costheading'                => Resources\CostHeading::class,
        'department'                 => Resources\Department::class,
        'employee'                   => Resources\Employee::class,
        'employeefamily'             => Resources\EmployeeFamily::class,
        'employeetarget'             => Resources\EmployeeTarget::class,
        'employeeYearlyLeaveBudget'  => Resources\EmployeeYearlyLeaveBudget::class,
        'employmentcontract'         => Resources\EmploymentContract::class,
        'externallink'               => Resources\ExternalLink::class,
        'file'                       => Resources\File::class,
        'hour'                       => Resources\Hour::class,
        'invoice'                    => Resources\Invoice::class,
        'invoiceline'                => Resources\InvoiceLine::class,
        'ledger'                     => Resources\Ledger::class,
        'memorial'                   => Resources\Memorial::class,
        'memorialline'               => Resources\MemorialLine::class,
        'notification'               => Resources\Notification::class,
        'offer'                      => Resources\Offer::class,
        'offerphase'                 => Resources\OfferPhase::class,
        'offerprojectline'           => Resources\OfferProjectLine::class,
        'packet'                     => Resources\Packet::class,
        'packetline'                 => Resources\PacketLine::class,
        'payment'                    => Resources\Payment::class,
        'priceexception'             => Resources\PriceException::class,
        'product'                    => Resources\Product::class,
        'project'                    => Resources\Project::class,
        'projectphase'               => Resources\ProjectPhase::class,
        'purchaseinvoice'            => Resources\PurchaseInvoice::class,
        'purchaseinvoiceline'        => Resources\PurchaseInvoiceLine::class,
        'purchaseorder'              => Resources\PurchaseOrder::class,
        'purchaseorderline'          => Resources\PurchaseOrderLine::class,
        'purchasepayment'            => Resources\PurchasePayment::class,
        'rejectionreason'            => Resources\RejectionReason::class,
        'revenuetarget'              => Resources\RevenueTarget::class,
        'tag'                        => Resources\Tag::class,
        'task'                       => Resources\Task::class,
        'taskphase'                  => Resources\TaskPhase::class,
        'tasktype'                   => Resources\TaskType::class,
        'timelineentry'              => Resources\TimelineEntry::class,
        'umbrellaproject'            => Resources\UmbrellaProject::class,
        'unit'                       => Resources\Unit::class,
        'webhook'                    => Resources\Webhook::class,
        'yeartarget'                 => Resources\YearTarget::class,
        'yeartargettype'             => Resources\YearTargetType::class,
    ];

    protected static ?string $token = null;

    protected static ?string $baseUrl = null;

    protected static ?JsonRpcClient $transport = null;

    public static function configure(?string $token = null, ?string $baseUrl = null): void
    {
        static::$token = $token ?? getenv('GRIPP_API_TOKEN') ?: ($_ENV['GRIPP_API_TOKEN'] ?? null);
        static::$baseUrl = $baseUrl ?? getenv('GRIPP_API_URL') ?: ($_ENV['GRIPP_API_URL'] ?? null);

        // Reset transport so it gets recreated with new config
        static::$transport = null;
    }

    public static function getTransport(): JsonRpcClient
    {
        if (static::$transport === null) {
            if (empty(static::$token) || empty(static::$baseUrl)) {
                throw new GrippException(
                    'GrippClient is not configured. Call GrippClient::configure($token, $url) first.'
                );
            }

            static::$transport = new JsonRpcClient(static::$token, static::$baseUrl);
        }

        return static::$transport;
    }

    public static function setTransport(JsonRpcClient $transport): void
    {
        static::$transport = $transport;
    }

    public static function getToken(): ?string
    {
        return static::$token;
    }

    public static function getBaseUrl(): ?string
    {
        return static::$baseUrl;
    }

    public static function reset(): void
    {
        static::$token = null;
        static::$baseUrl = null;
        static::$transport = null;
    }
}
