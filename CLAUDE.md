# Gripp SDK for PHP

This is a PHP SDK (`codebes/gripp-sdk`) for the Gripp business management JSON-RPC API.

## Project Structure

```
src/
├── GrippClient.php                  # Static facade — configure token/URL here first
├── Transport/
│   ├── JsonRpcClient.php            # HTTP transport (batch, pagination, retries)
│   └── JsonRpcResponse.php          # Response wrapper (rows, count, toCollection)
├── Query/
│   ├── QueryBuilder.php             # Fluent filter builder (where/orderBy/limit/get)
│   └── Filter.php                   # Filter value object (field, operator, value)
├── Resources/
│   ├── Resource.php                 # Abstract base class — all 54 resources extend this
│   ├── Concerns/
│   │   ├── CanRead.php              # get(), find(), all(), where(), first()
│   │   ├── CanCreate.php            # create()
│   │   ├── CanUpdate.php            # update()
│   │   └── CanDelete.php            # delete()
│   └── [54 resource classes]        # Company, Contact, Project, Invoice, etc.
└── Exceptions/
    ├── GrippException.php           # Base exception
    ├── AuthenticationException.php  # 401/403
    ├── RateLimitException.php       # 429
    └── RequestException.php         # Other API errors
```

## Key Patterns

- **Always call `GrippClient::configure()` before using any resource.** It reads from `GRIPP_API_TOKEN` and `GRIPP_API_URL` env vars if no arguments are given.
- **Resources are static classes.** Usage: `Company::find(123)`, `Company::create([...])`, `Project::where('name', 'contains', 'foo')->get()`.
- **Traits compose CRUD capabilities.** Check which traits a resource `use`s to know what operations are available. Some resources are read-only (e.g. `Cost`, `File`, `Memorial`).
- **Each resource has schema constants:** `FIELDS` (field→type), `READONLY`, `REQUIRED`, `RELATIONS` (FK→class).
- **JSON-RPC methods follow the pattern:** `{entity}.{action}` (e.g. `company.get`, `project.create`).
- **The `Notification` resource is special** — it uses `emit()` and `emitall()` instead of standard CRUD.

## Filter Operators

When using `where($field, $operator, $value)`:

equals, notequals, contains, notcontains, startswith, endswith, greaterthan, lessthan, greaterthanorequal, lessthanorequal, in, notin, isnull, isnotnull

Two-argument `where($field, $value)` defaults to `equals`.

## Development

```bash
composer install
vendor/bin/phpunit          # Run tests
vendor/bin/gripp-setup      # Interactive credential setup
```

## Coding Conventions

- PSR-4 autoloading under `CodeBes\GrippSdk\`
- Static methods on all resources (no instantiation needed)
- Returns `Illuminate\Support\Collection` for multi-record queries, `?array` for single records
- Entity names are lowercase concatenation of class name: `ProjectPhase` → `'projectphase'`
- Fields use the Gripp API naming conventions (lowercase, no underscores between words in entity names)
