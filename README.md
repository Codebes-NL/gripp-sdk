# Gripp SDK for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebes/gripp-sdk.svg)](https://packagist.org/packages/codebes/gripp-sdk)
[![Tests](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/tests.yml/badge.svg)](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/tests.yml)
[![PHPStan](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/Codebes-NL/gripp-sdk/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/codebes/gripp-sdk.svg)](https://packagist.org/packages/codebes/gripp-sdk)

A PHP SDK for the [Gripp](https://www.gripp.com) CRM/ERP API. Manage companies, contacts, projects, invoices, time tracking, and 50+ other resources through a fluent query builder with batch operations, auto-pagination, and automatic retries. Works with any PHP 8.1+ application, including Laravel.

## Features

- Fluent query builder with 14 filter operators
- Full CRUD support on 54 Gripp resources
- Batch operations (multiple API calls in a single HTTP request)
- Auto-pagination for large datasets
- Automatic retries on server errors and connection failures
- Typed exceptions for authentication, rate limiting, and API errors
- Laravel Collection responses out of the box
- Self-documenting resources with field types, required fields, and relationship metadata

## Requirements

- PHP 8.1+
- A Gripp API token and API URL

## Installation

```bash
composer require codebes/gripp-sdk
```

## Configuration

### Option 1: Environment variables

Set `GRIPP_API_TOKEN` and `GRIPP_API_URL` in your `.env` file or environment:

```env
GRIPP_API_TOKEN=your-api-token
GRIPP_API_URL=https://your-tenant.gripp.com
```

Then call configure without arguments:

```php
use CodeBes\GrippSdk\GrippClient;

GrippClient::configure();
```

### Option 2: Explicit configuration

```php
use CodeBes\GrippSdk\GrippClient;

GrippClient::configure(
    token: 'your-api-token',
    baseUrl: 'https://your-tenant.gripp.com'
);
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
| `greaterthanorequal` | Greater than or equal to |
| `lessthanorequal` | Less than or equal to |
| `in` | Value is in array |
| `notin` | Value is not in array |
| `isnull` | Field is null (pass `true` as value) |
| `isnotnull` | Field is not null (pass `true` as value) |

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

The `all()` method automatically handles pagination, fetching all records transparently:

```php
// Fetches all companies, regardless of how many pages it takes
$companies = Company::all(); // Returns Illuminate\Support\Collection
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
