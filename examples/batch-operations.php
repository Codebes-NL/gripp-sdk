<?php

/**
 * Batch operations with the Gripp SDK.
 *
 * Demonstrates: batching multiple API calls into a single HTTP request.
 * This improves performance when you need to make many calls at once.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Resources\Contact;
use CodeBes\GrippSdk\Resources\Project;

GrippClient::configure();

// -----------------------------------------------
// 1. Basic batch — multiple reads in one request
// -----------------------------------------------

$transport = GrippClient::getTransport();
$transport->startBatch();

// These calls are queued, not executed yet
Company::find(1);
Company::find(2);
Company::find(3);

// Execute all queued calls in a single HTTP request
$responses = $transport->executeBatch();

foreach ($responses as $index => $response) {
    $rows = $response->rows();
    if (!empty($rows)) {
        echo "Company {$index}: " . ($rows[0]['companyname'] ?? 'N/A') . "\n";
    }
}

// -----------------------------------------------
// 2. Mixed resource batch
// -----------------------------------------------

$transport->startBatch();

Company::find(1);
Contact::find(10);
Project::find(100);

$responses = $transport->executeBatch();

$company = $responses[0]->rows()[0] ?? null;
$contact = $responses[1]->rows()[0] ?? null;
$project = $responses[2]->rows()[0] ?? null;

// -----------------------------------------------
// 3. Batch with different operations
// -----------------------------------------------

$transport->startBatch();

// Mix reads and writes in a single batch
Company::get([
    ['field' => 'company.active', 'operator' => 'equals', 'value' => true],
], ['paging' => ['firstresult' => 0, 'maxresults' => 10]]);

Contact::get([
    ['field' => 'contact.company', 'operator' => 'equals', 'value' => 42],
]);

$responses = $transport->executeBatch();

$companies = $responses[0]->toCollection();
$contacts = $responses[1]->toCollection();

echo "Active companies: " . $companies->count() . "\n";
echo "Contacts for company 42: " . $contacts->count() . "\n";

// -----------------------------------------------
// 4. Track request count
// -----------------------------------------------

// The transport tracks how many HTTP requests have been made
echo "Total HTTP requests: " . $transport->getRequestCount() . "\n";
// A batch counts as a single HTTP request, regardless of how many calls are inside
