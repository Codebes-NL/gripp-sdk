<?php

/**
 * Error handling with the Gripp SDK.
 *
 * Demonstrates: exception types, retry logic, common error scenarios.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Exceptions\AuthenticationException;
use CodeBes\GrippSdk\Exceptions\RateLimitException;
use CodeBes\GrippSdk\Exceptions\RequestException;
use CodeBes\GrippSdk\Exceptions\GrippException;

GrippClient::configure();

// -----------------------------------------------
// 1. Full exception handling pattern
// -----------------------------------------------

try {
    $company = Company::find(123);
} catch (AuthenticationException $e) {
    // HTTP 401 or 403
    if ($e->isTokenInvalid()) {
        echo "Token is invalid or expired. Please reconfigure.\n";
        // Consider calling GrippClient::configure() with a new token
    }
    if ($e->isForbidden()) {
        echo "Insufficient permissions for this operation.\n";
    }
    echo "Status code: " . $e->getStatusCode() . "\n";
} catch (RateLimitException $e) {
    // HTTP 429
    $retryAfter = $e->getRetryAfter();
    $remaining = $e->getRemaining();

    echo "Rate limited. Retry after: {$retryAfter}s\n";
    echo "Remaining requests: {$remaining}\n";

    // Wait and retry
    if ($retryAfter) {
        sleep($retryAfter);
        $company = Company::find(123); // retry
    }
} catch (RequestException $e) {
    // Other API errors (invalid parameters, server errors, etc.)
    echo "API error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";

    $data = $e->getResponseData();
    if ($data) {
        echo "Error details: " . json_encode($data) . "\n";
    }
} catch (GrippException $e) {
    // Base exception — catches configuration errors too
    // e.g., "GrippClient is not configured"
    echo "SDK error: " . $e->getMessage() . "\n";
}

// -----------------------------------------------
// 2. Handling "not configured" errors
// -----------------------------------------------

try {
    // This will throw GrippException if configure() hasn't been called
    GrippClient::reset();
    Company::find(1);
} catch (GrippException $e) {
    echo $e->getMessage() . "\n";
    // "GrippClient is not configured. Call GrippClient::configure($token, $url) first."

    // Fix it
    GrippClient::configure();
}

// -----------------------------------------------
// 3. Using exception data for logging
// -----------------------------------------------

try {
    Company::create(['companyname' => 'Test']);
    // Missing required field 'relationtype' — API will return an error
} catch (GrippException $e) {
    // All SDK exceptions support toArray() for structured logging
    $errorData = $e->toArray();
    // [
    //     'exception' => 'CodeBes\GrippSdk\Exceptions\RequestException',
    //     'message' => 'Field "relationtype" is required',
    //     'code' => 0,
    //     'rpc_method' => null,
    // ]

    error_log(json_encode($errorData));
}

// -----------------------------------------------
// 4. Note on automatic retries
// -----------------------------------------------

// The SDK automatically retries on:
// - 5xx server errors (up to 3 attempts)
// - Connection failures (up to 3 attempts)
//
// You do NOT need to implement retry logic for these cases.
// Only RateLimitException (429) needs manual retry handling.
