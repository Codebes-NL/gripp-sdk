<?php

namespace CodeBes\GrippSdk\Resources;

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Transport\JsonRpcResponse;

/**
 * Abstract base class for all Gripp API resources.
 *
 * Each resource class represents a Gripp entity (e.g. company, project, invoice)
 * and provides static methods for CRUD operations via trait composition.
 *
 * Every resource exposes four constants describing its schema:
 * - FIELDS: array<string, string>  - maps field names to types (string, int, float, boolean, datetime, date, array, customfields, color)
 * - READONLY: string[]  - fields that cannot be written
 * - REQUIRED: string[]  - fields that must be provided on create/update
 * - RELATIONS: array<string, class-string<Resource>>  - maps FK fields to related resource classes
 *
 * @example
 * // All resources use static methods  - no instantiation needed.
 *
 * // Read operations (via CanRead trait)
 * $record = Company::find(123);        // Single record by ID → ?array
 * $all = Company::all();               // All records (auto-paginated) → Collection
 * $filtered = Company::where('active', true)->get(); // Query builder → Collection
 *
 * // Create (via CanCreate trait)
 * $result = Company::create(['companyname' => 'Acme', 'relationtype' => 'COMPANY']);
 *
 * // Update (via CanUpdate trait)
 * Company::update(123, ['phone' => '+31201234567']);
 *
 * // Delete (via CanDelete trait)
 * Company::delete(123);
 *
 * // Check available fields and requirements
 * Company::FIELDS;    // All fields and their types
 * Company::REQUIRED;  // Fields that must be provided
 * Company::READONLY;  // Fields that can't be written
 * Company::RELATIONS; // Foreign key mappings
 */
abstract class Resource
{
    abstract protected static function entity(): string;

    protected static function rpcCall(string $action, array $params = []): JsonRpcResponse
    {
        $method = static::entity() . '.' . $action;

        return GrippClient::getTransport()->call($method, $params);
    }
}
