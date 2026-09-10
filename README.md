# Gripp SDK for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebes/gripp-sdk.svg)](https://packagist.org/packages/codebes/gripp-sdk)
[![Tests](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/tests.yml/badge.svg)](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/tests.yml)
[![PHPStan](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/codebes/gripp-sdk.svg)](https://packagist.org/packages/codebes/gripp-sdk)

A PHP SDK for the [Gripp](https://www.gripp.com) (now [Exact Gripp](https://www.exact.com/nl/producten/exact-gripp)) CRM/ERP API. Manage companies, contacts, projects, invoices, time tracking, and 50+ other resources through a fluent query builder with batch operations, auto-pagination, and automatic retries. Works with any PHP 8.1+ application, including Laravel.

## Features

- Fluent query builder with 14 filter operators
- Full CRUD support on 54 Gripp resources
- Batch operations (multiple API calls in a single HTTP request)
- Auto-pagination for large datasets
- Automatic retries on server errors and connection failures
- Typed exceptions for authentication, rate limiting, and API errors
- Laravel Collection responses out of the box
- Self-documenting resources with field types, required fields, and relationship metadata
- Billability (declarabiliteit) and invoiceability (facturabiliteit) calculations, with an explicit account of what is exact and what is estimated

## Requirements

- PHP 8.1+
- A Gripp API token and API URL

## Installation

```bash
composer require codebes/gripp-sdk
```

## Configuration

### Option 1: Environment variables

Set `GRIPP_API_TOKEN` in your `.env` file or environment:

```env
GRIPP_API_TOKEN=your-api-token
```

Then call configure without arguments:

```php
use CodeBes\GrippSdk\GrippClient;

GrippClient::configure();
```

The SDK uses `https://api.gripp.com` by default. To override, set `GRIPP_API_URL` in your environment.

### Option 2: Explicit configuration

```php
use CodeBes\GrippSdk\GrippClient;

GrippClient::configure(token: 'your-api-token');
```

### Option 3: Interactive setup

```bash
vendor/bin/gripp-setup
```

This creates a `.env` file with your credentials.

## Quick Start

```php
use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Resources\Contact;
use CodeBes\GrippSdk\Resources\Project;

// Configure once at application boot
GrippClient::configure();

// Find a record by ID
$company = Company::find(123);

// Get all records (auto-paginated)
$allCompanies = Company::all();

// Query with filters
$activeCompanies = Company::where('active', true)
    ->orderBy('companyname', 'asc')
    ->limit(50)
    ->get();

// Create a record
$result = Company::create([
    'companyname' => 'Acme Corp',
    'relationtype' => 'COMPANY',
    'email' => 'info@acme.com',
]);

// Update a record
Company::update(123, [
    'phone' => '+31 20 123 4567',
]);

// Delete a record
Company::delete(123);
```

## Query Builder

The query builder provides a fluent interface for filtering, ordering, and paginating results.

```php
use CodeBes\GrippSdk\Resources\Project;

// Simple equality filter (two-argument form)
$projects = Project::where('company', 42)->get();

// With operator (three-argument form)
$projects = Project::where('name', 'contains', 'Website')->get();

// Chain multiple filters
$results = Project::where('company', 42)
    ->where('archived', false)
    ->orderBy('createdon', 'desc')
    ->limit(25)
    ->offset(0)
    ->get();

// Get just the first match
$project = Project::where('name', 'contains', 'Redesign')->first();

// Count matching records
$count = Project::where('archived', false)->count();
```

### Supported Filter Operators

| Operator | Description |
|---|---|
| `equals` | Exact match (default when using two-argument `where`) |
| `notequals` | Not equal to |
| `contains` | String contains |
| `notcontains` | String does not contain |
| `startswith` | String starts with |
| `endswith` | String ends with |
| `greaterthan` | Greater than |
| `lessthan` | Less than |
| `greaterequals` | Greater than or equal to |
| `lessequals` | Less than or equal to |
| `in` | Value is in array |
| `notin` | Value is not in array |
| `isnull` | Field is null (pass `true` as value) |
| `isnotnull` | Field is not null (pass `true` as value) |

### Date Helpers

Common date filtering patterns are built into the query builder:

```php
use CodeBes\GrippSdk\Resources\Project;
use CodeBes\GrippSdk\Resources\Hour;

// Filter by year
$projects = Project::where('archived', false)
    ->whereYear('createdon', 2026)
    ->get();

// Filter by month
$hours = Hour::where('employee', 42)
    ->whereMonth('date', 2026, 3)
    ->get();

// Filter by date range
$invoices = Invoice::where('company', 10)
    ->whereDateBetween('date', '2026-01-01', '2026-03-31')
    ->get();

// Created or modified since (for incremental syncing)
$updated = Project::where('archived', false)
    ->whereModifiedSince(new DateTime('2026-03-01 00:00:00'))
    ->get();

// Start a query without a filter, when the first thing you need is a helper
$recent = Hour::query()
    ->whereModifiedSince(new DateTime('2026-03-01 00:00:00'))
    ->get();
```

> **`whereModifiedSince()` sends two requests, on purpose.** Gripp leaves `updatedon`
> empty on records that were created and never edited afterwards, so filtering on
> `updatedon` alone silently omits every untouched record. The correct condition is
> `updatedon >= $date OR createdon >= $date`, and the Gripp API has no OR - every
> filter in the array is ANDed. So the SDK runs both halves and merges them on `id`.

### Auto-Prefixing

Field names are automatically prefixed with the entity name when using the query builder. Writing `Project::where('createdon', ...)` produces the filter field `project.createdon`. Fully qualified fields like `project.createdon` are left as-is.

### Auto-Pagination

Both `get()` and the query builder's `get()` automatically paginate through all results. Use `limit()` when you only want a specific number of results (single API call):

```php
// Fetches ALL matching projects across all pages
$all = Project::where('archived', false)->get();

// Fetches only the first 25 (single page)
$page = Project::where('archived', false)->limit(25)->get();
```

## Batch Operations

Group multiple API calls into a single HTTP request for better performance:

```php
use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Resources\Contact;

$transport = GrippClient::getTransport();
$transport->startBatch();

// Queue multiple calls (these don't execute yet)
Company::find(1);
Company::find(2);
Contact::find(10);

// Execute all queued calls in a single HTTP request
$responses = $transport->executeBatch();

foreach ($responses as $response) {
    $rows = $response->rows();
    // Process each response...
}
```

## Rate Limit Awareness

The transport tracks rate limit headers from API responses and provides hooks for proactive budget management:

```php
$transport = GrippClient::getTransport();

// Check current rate limit state (from most recent response headers)
$transport->getRateLimitRemaining(); // e.g. 847
$transport->getRateLimitLimit();     // e.g. 1000

// Abort requests when budget is low
$transport->beforeRequest(function (int $requestCount, ?int $remaining, ?int $limit) {
    if ($remaining !== null && $remaining <= 5) {
        throw new \RuntimeException("Only {$remaining} API calls left!");
    }
});

// React when a 429 or 503 rate limit hits
$transport->onRateLimitExceeded(function (?int $retryAfter, ?int $remaining) {
    // Set a flag, notify monitoring, etc.
    Log::warning("Gripp rate limit hit, retry after {$retryAfter}s");
});
```

The SDK treats both HTTP 429 and HTTP 503 with Gripp error code 1004 (short-burst throttle) as rate limit errors.

## Error Handling

The SDK throws specific exceptions for different error types:

```php
use CodeBes\GrippSdk\Exceptions\AuthenticationException;
use CodeBes\GrippSdk\Exceptions\RateLimitException;
use CodeBes\GrippSdk\Exceptions\RequestException;
use CodeBes\GrippSdk\Exceptions\GrippException;

try {
    $company = Company::find(123);
} catch (AuthenticationException $e) {
    // 401 or 403 - invalid token or forbidden
    if ($e->isTokenInvalid()) {
        // Handle invalid/expired token
    }
    if ($e->isForbidden()) {
        // Handle insufficient permissions
    }
} catch (RateLimitException $e) {
    // 429 - too many requests
    $retryAfter = $e->getRetryAfter(); // seconds to wait
    $remaining = $e->getRemaining();   // remaining requests
} catch (RequestException $e) {
    // Other API errors
    $data = $e->getResponseData(); // raw error response
} catch (GrippException $e) {
    // Base exception for all SDK errors (e.g. not configured)
}
```

## Available Resources

All resources support **read** operations. Resources that also support create, update, and/or delete are indicated below.

| Resource | Create | Read | Update | Delete |
|---|:---:|:---:|:---:|:---:|
| `AbsenceRequest` | x | x | x | x |
| `AbsenceRequestLine` | x | x | x | x |
| `BulkPrice` | x | x | x | x |
| `CalendarItem` | x | x | x | x |
| `Company` | x | x | x | x |
| `CompanyDossier` | x | x | x | x |
| `Contact` | x | x | x | x |
| `Contract` | x | x | x | x |
| `ContractLine` | x | x | x | x |
| `Cost` | | x | | |
| `CostHeading` | x | x | x | x |
| `Department` | x | x | x | x |
| `Employee` | x | x | x | x |
| `EmployeeFamily` | x | x | x | x |
| `EmployeeTarget` | | x | | |
| `EmployeeYearlyLeaveBudget` | x | x | x | |
| `EmploymentContract` | x | x | x | x |
| `ExternalLink` | x | x | x | x |
| `File` | | x | | |
| `Hour` | x | x | x | x |
| `Invoice` | x | x | x | x |
| `InvoiceLine` | x | x | x | x |
| `Ledger` | x | x | x | x |
| `Memorial` | | x | | |
| `MemorialLine` | | x | | |
| `Notification` | | | | |
| `Offer` | x | x | x | x |
| `OfferPhase` | x | x | x | x |
| `OfferProjectLine` | x | x | x | x |
| `Packet` | x | x | x | x |
| `PacketLine` | x | x | x | x |
| `Payment` | x | x | x | x |
| `PriceException` | x | x | x | x |
| `Product` | x | x | x | x |
| `Project` | x | x | x | x |
| `ProjectPhase` | x | x | x | x |
| `PurchaseInvoice` | x | x | x | x |
| `PurchaseInvoiceLine` | x | x | x | x |
| `PurchaseOrder` | x | x | x | x |
| `PurchaseOrderLine` | x | x | x | x |
| `PurchasePayment` | x | x | x | x |
| `RejectionReason` | x | x | x | x |
| `RevenueTarget` | | x | | |
| `Tag` | x | x | x | x |
| `Task` | x | x | x | x |
| `TaskPhase` | x | x | x | x |
| `TaskType` | x | x | x | x |
| `TimelineEntry` | x | x | x | x |
| `UmbrellaProject` | x | x | x | |
| `Unit` | x | x | x | x |
| `Webhook` | x | x | x | x |
| `YearTarget` | | x | | |
| `YearTargetType` | | x | | |

**Special resources:**
- `Notification` has custom `emit()` and `emitall()` methods instead of CRUD.
- `Company` has additional `getCompanyByCOC()`, `addInteractionByCompanyId()`, and `addInteractionByCompanyCOC()` methods.

## Resource Metadata

Every resource class exposes constants that describe its schema:

```php
use CodeBes\GrippSdk\Resources\Company;

Company::FIELDS;    // ['id' => 'int', 'companyname' => 'string', ...]
Company::READONLY;  // ['createdon', 'updatedon', 'id', 'searchname', 'files']
Company::REQUIRED;  // ['relationtype']
Company::RELATIONS; // ['accountmanager' => Employee::class, 'tags' => Tag::class, ...]
```

- `FIELDS` maps field names to their types (`string`, `int`, `float`, `boolean`, `datetime`, `date`, `array`, `customfields`, `color`)
- `READONLY` lists fields that cannot be written to
- `REQUIRED` lists fields that must be provided when creating/updating
- `RELATIONS` maps foreign key fields to their related resource classes

## Auto-Pagination

Both `all()` and `get()` automatically handle pagination, fetching all matching records transparently:

```php
// Fetches all companies, regardless of how many pages it takes
$companies = Company::all(); // Returns Illuminate\Support\Collection

// Filtered queries also auto-paginate
$active = Company::where('active', true)->get(); // All pages
```

## Response Format

All collection methods return `Illuminate\Support\Collection` instances. Single-record methods return associative arrays or `null`.

```php
$companies = Company::where('active', true)->get();

// Use Collection methods
$names = $companies->pluck('companyname');
$grouped = $companies->groupBy('visitingaddress_city');
$first = $companies->first();
```

## Billability and Invoiceability

`CodeBes\GrippSdk\Features\Billability` computes two measures from hours, project lines and invoice lines. In Dutch they have different names, and they answer different questions.

| Measure | Dutch | Question | Formula |
|---|---|---|---|
| Billability | declarabiliteit | How much of the time went to paid work? | hours on paid project lines / all hours |
| Invoiceability | facturabiliteit | How much of the paid work was actually invoiced? | invoiced hours / hours on paid project lines |

Ten hours on a paid project line are 100% billable. If five of them end up on an invoice, invoiceability is 50%.

```php
use CodeBes\GrippSdk\Features\Billability;

Billability::forEmployee(42, '2025-01-01', '2025-12-31');               // billability
Billability::forTeam('2025-01-01', '2025-12-31');

Billability::invoiceabilityForEmployee(42, '2025-01-01', '2025-12-31'); // invoiceability
Billability::invoiceabilityForTeam('2025-01-01', '2025-12-31');         // per employee and in total
```

### Why invoiceability is exact for some hours and estimated for others

Whether an hour counts as invoiced depends on the invoice basis (`invoicebasis`) of the project line it was written on, because Gripp records the link between hours and invoices differently per basis:

| Invoice basis | Dutch | How Gripp links the invoice | Precision |
|---|---|---|---|
| `COSTING` | Nacalculatie | Every invoiced hour carries `hour.invoiceline`. | Exact, per hour |
| `BUDGETED` | Begroot | Every invoiced hour carries `hour.invoiceline`. | Exact, per hour |
| `FIXED` | Fixed | No hour ever carries `hour.invoiceline`. The invoice line points at the project line (`invoiceline.part`) with a quantity. | Exact per project line; per person only when one person worked on the line |
| `NONBILLABLE` | Niet doorbelasten | Not invoiced. | Not part of invoiceability |

This was verified against a full year of live Gripp data: for Nacalculatie and Begroot, the quantity on every invoice line in hours equals the sum of the hours linked to it, and not a single hour on a Fixed line had an invoice line. The limit is in how Gripp records Fixed invoicing, not in the SDK. No API call can tell who a Fixed invoice line was for.

For Fixed lines the SDK therefore works per project line:

1. **Over the line's whole life.** All hours ever written on the line are set against all hour quantities ever invoiced on it, regardless of the requested period. Fixed work is often invoiced up front or in instalments, so comparing within one period would pair invoices with the wrong work.
2. **Not capped at the hours worked.** Sell and invoice 35 hours, work 10, and those 10 hours count as 35 invoiced hours: 350% on that line.
3. **Split pro rata when a line is shared.** When several people wrote hours on the line, each of their hours gets the line's ratio of invoiced hours to hours written. This is the only assumption in the calculation: totals per line, per project and per team add up exactly.
4. **Credit lines count negative** on the project line they point at.
5. **Lines invoiced in other units are left out.** Pieces (`stuks`), a fixed price (`prijs`) or any unit other than `uur` has no hour quantity to compare with. `Unit.hoursperunit` does not help, because Gripp returns 1 for every unit. These hours are reported as `unmeasurable_hours` and excluded from the percentage.

Every invoiceability result says how much of it is exact:

| Key | Meaning |
|---|---|
| `billable_hours` | Hours on Fixed, Nacalculatie and Begroot lines |
| `invoiced_hours` | Invoiced hours as described above; can exceed the hours worked on Fixed lines |
| `uninvoiced_hours` | Billable hours not covered by invoicing |
| `exact_hours` | Nacalculatie and Begroot hours, and Fixed hours on lines that one person worked on or that were never invoiced |
| `estimated_hours` | Fixed hours on shared, invoiced lines, split pro rata |
| `unmeasurable_hours` | Fixed hours on lines invoiced in units other than hours |
| `invoiceability_percentage` | `invoiced_hours / (billable_hours - unmeasurable_hours) * 100` |
| `by_invoice_basis` | Billable and invoiced hours per invoice basis |

To make Fixed work exact per person, it has to be recorded differently in Gripp: one project line per person on Fixed projects, or the Begroot basis, where Gripp does link hours to invoices.

> **Cost.** Invoiceability fetches the period's hours, plus the lifetime hours and invoice lines of every Fixed line in the period, 100 lines per request. For a team of about twenty people over a full year that took 206 API calls, around 100 seconds and 212 MB of memory, above PHP's default `memory_limit` of 128 MB. Raise the limit, or ask per employee or per quarter and add up the hours. `forTeam()` loads the same hours and needs the same memory.

`uninvoicedHours()` lists hours without `hour.invoiceline`. That includes every hour on a Fixed line, invoiced or not, so do not use it to find uninvoiced Fixed work.

## Testing

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

## Changelog

Please see the [GitHub Releases](https://github.com/Codebes-NL/gripp-sdk/releases) page for more information on what has changed recently.

## Contributing

Contributions are welcome! Please open a pull request against the `main` branch. All PRs require:
- Passing tests (`composer test`)
- Code style compliance (`composer cs`)
- Static analysis passing (`composer analyse`)
- Code owner approval

## License

MIT - see [LICENSE](LICENSE) for details.
