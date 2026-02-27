<?php

/**
 * Query builder usage for the Gripp SDK.
 *
 * Demonstrates: where filters, operators, ordering, pagination, chaining.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Resources\Project;
use CodeBes\GrippSdk\Resources\Invoice;
use CodeBes\GrippSdk\Resources\Task;

GrippClient::configure();

// -----------------------------------------------
// 1. Simple equality filter (two-argument form)
//    Defaults to 'equals' operator.
// -----------------------------------------------

$companies = Company::where('active', true)->get();
// SQL equivalent: WHERE active = true

// -----------------------------------------------
// 2. Operator filter (three-argument form)
//    Supported operators:
//    equals, notequals, contains, notcontains,
//    startswith, endswith, greaterthan, lessthan,
//    greaterthanorequal, lessthanorequal,
//    in, notin, isnull, isnotnull
// -----------------------------------------------

// String contains
$results = Company::where('companyname', 'contains', 'Tech')->get();

// Starts with
$results = Company::where('companyname', 'startswith', 'A')->get();

// Greater than (dates, numbers)
$recent = Project::where('createdon', 'greaterthan', '2025-01-01')->get();

// In a set of values
$specific = Company::where('id', 'in', [1, 2, 3, 4, 5])->get();

// Not null
$withEmail = Company::where('email', 'isnotnull', true)->get();

// -----------------------------------------------
// 3. Chaining multiple filters (AND logic)
// -----------------------------------------------

$filtered = Project::where('company', 42)
    ->where('archived', false)
    ->where('name', 'contains', 'Website')
    ->get();

// -----------------------------------------------
// 4. Ordering results
// -----------------------------------------------

$ordered = Company::where('active', true)
    ->orderBy('companyname', 'asc')
    ->get();

// Multiple orderings
$ordered = Project::where('archived', false)
    ->orderBy('company', 'asc')
    ->orderBy('createdon', 'desc')
    ->get();

// -----------------------------------------------
// 5. Pagination (limit & offset)
// -----------------------------------------------

// First 25 results
$page1 = Company::where('active', true)
    ->orderBy('companyname', 'asc')
    ->limit(25)
    ->offset(0)
    ->get();

// Next 25 results
$page2 = Company::where('active', true)
    ->orderBy('companyname', 'asc')
    ->limit(25)
    ->offset(25)
    ->get();

// -----------------------------------------------
// 6. Get first matching record
// -----------------------------------------------

$newest = Project::where('company', 42)
    ->orderBy('createdon', 'desc')
    ->first();
// Returns: array or null

// -----------------------------------------------
// 7. Count matching records
// -----------------------------------------------

$count = Task::where('project', 10)
    ->where('taskphase', 'notequals', 3)
    ->count();
// Returns: int

// -----------------------------------------------
// 8. Working with Collection results
// -----------------------------------------------

$projects = Project::where('archived', false)->get();

// Pluck a single field
$names = $projects->pluck('name');

// Group by a field
$byCompany = $projects->groupBy('company');

// Filter in memory
$large = $projects->filter(fn ($p) => ($p['totalexclvat'] ?? 0) > 10000);

// Map to transform
$summaries = $projects->map(fn ($p) => [
    'id' => $p['id'],
    'name' => $p['name'],
    'total' => $p['totalexclvat'] ?? 0,
]);
