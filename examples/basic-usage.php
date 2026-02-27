<?php

/**
 * Basic CRUD usage of the Gripp SDK.
 *
 * Demonstrates: configuration, find, all, create, update, delete.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Resources\Contact;
use CodeBes\GrippSdk\Resources\Project;

// -----------------------------------------------
// 1. Configure the client (required before any API call)
// -----------------------------------------------

// Option A: Pass credentials directly
GrippClient::configure(
    token: 'your-api-token',
    baseUrl: 'https://your-tenant.gripp.com'
);

// Option B: Read from environment variables (GRIPP_API_TOKEN, GRIPP_API_URL)
// GrippClient::configure();

// -----------------------------------------------
// 2. Find a single record by ID
// -----------------------------------------------

$company = Company::find(123);
// Returns: ['id' => 123, 'companyname' => 'Acme Corp', ...] or null

// -----------------------------------------------
// 3. Get all records (auto-paginated)
// -----------------------------------------------

$allCompanies = Company::all();
// Returns: Illuminate\Support\Collection of all companies

echo "Total companies: " . $allCompanies->count() . "\n";

// -----------------------------------------------
// 4. Create a new record
// -----------------------------------------------

// Check Company::REQUIRED to see which fields are mandatory.
// For Company, 'relationtype' is required.
$result = Company::create([
    'companyname' => 'New Client BV',
    'relationtype' => 'COMPANY',
    'email' => 'info@newclient.nl',
    'phone' => '+31 20 123 4567',
    'visitingaddress_city' => 'Amsterdam',
    'visitingaddress_country' => 'NL',
]);

// -----------------------------------------------
// 5. Update an existing record
// -----------------------------------------------

// Only pass the fields you want to change.
// Check Company::READONLY to see which fields cannot be updated.
Company::update(123, [
    'phone' => '+31 20 765 4321',
    'website' => 'https://newclient.nl',
]);

// -----------------------------------------------
// 6. Delete a record
// -----------------------------------------------

Company::delete(123);

// -----------------------------------------------
// 7. Work with related resources
// -----------------------------------------------

// Create a contact linked to a company
// Check Contact::REQUIRED — 'company' (FK → Company) is required.
$contactResult = Contact::create([
    'company' => 42,
    'firstname' => 'Jan',
    'lastname' => 'de Vries',
    'email' => 'jan@example.com',
    'function' => 'CTO',
]);

// Create a project linked to a company
// Check Project::REQUIRED — 'templateset', 'name', 'company' are required.
$projectResult = Project::create([
    'templateset' => 1,
    'name' => 'Website Redesign',
    'company' => 42,
    'description' => 'Full redesign of the corporate website.',
    'startdate' => '2025-03-01',
]);

// -----------------------------------------------
// 8. Inspect resource metadata
// -----------------------------------------------

// See all fields and their types
print_r(Company::FIELDS);
// ['id' => 'int', 'companyname' => 'string', 'email' => 'string', ...]

// See which fields are read-only
print_r(Company::READONLY);
// ['createdon', 'updatedon', 'id', 'searchname', 'files']

// See which fields are required for create/update
print_r(Company::REQUIRED);
// ['relationtype']

// See foreign key relationships
print_r(Company::RELATIONS);
// ['accountmanager' => Employee::class, 'tags' => Tag::class, 'files' => File::class]
