# AI Agent Instructions - Careem Now to Loyverse POS Integration

## Overview

This document provides comprehensive guidelines for AI agents working on this project. Follow these instructions carefully to ensure code quality, consistency, and proper documentation.

## Before You Start

### 1. Read Required Documentation
Before implementing any feature, **ALWAYS** read the following files in order:
1. **Context.md** - Understand the project overview, architecture, and requirements
2. **Tasks.md** - Review the specific task you're working on
3. **changelog.md** - Check what has been implemented already
4. **Deployment.md** - Understand the deployment context

### 2. Understand the Task
- Identify which phase and task from Tasks.md you're working on
- Understand the dependencies (what must be completed first)
- Review related code if the task builds on existing functionality

### 3. Plan Before Coding
- Break down the task into smaller steps
- Identify which files need to be created/modified
- Consider edge cases and error scenarios
- Think about testing requirements

## Project Structure

```
careem-loyverse-integration/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── WebhookController.php
│   │   │   └── Dashboard/
│   │   │       ├── OrderController.php
│   │   │       ├── SyncLogController.php
│   │   │       └── SettingsController.php
│   │   ├── Middleware/
│   │   │   └── VerifyWebhookSignature.php
│   │   └── Requests/
│   │       └── CareemOrderRequest.php
│   ├── Models/
│   │   ├── Order.php
│   │   ├── LoyverseOrder.php
│   │   ├── SyncLog.php
│   │   ├── WebhookLog.php
│   │   └── ApiCredential.php
│   ├── Jobs/
│   │   ├── ProcessCareemOrderJob.php
│   │   ├── SyncToLoyverseJob.php
│   │   └── RetryFailedSyncJob.php
│   ├── Services/
│   │   ├── CareemService.php
│   │   ├── LoyverseService.php
│   │   ├── OrderTransformerService.php
│   │   └── SyncService.php
│   ├── Repositories/
│   │   ├── OrderRepository.php
│   │   └── SyncLogRepository.php
│   ├── Events/
│   │   ├── OrderReceived.php
│   │   ├── OrderSynced.php
│   │   └── SyncFailed.php
│   └── Exceptions/
│       ├── LoyverseApiException.php
│       └── WebhookValidationException.php
├── database/
│   └── migrations/
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       └── dashboard/
│           ├── orders/
│           ├── logs/
│           └── settings/
├── routes/
│   ├── web.php
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── config/
│   └── loyverse.php
└── public/
```

## Coding Standards

### Laravel Best Practices

#### 1. Controllers
- **Keep controllers thin** - Move business logic to services
- **Use Form Requests** for validation
- **Return consistent responses**

```php
// Good ✓
public function store(CareemOrderRequest $request)
{
    $order = $this->orderService->createOrder($request->validated());
    return response()->json(['success' => true, 'order' => $order]);
}

// Bad ✗
public function store(Request $request)
{
    // Heavy business logic and validation in controller
}
```

#### 2. Services
- **Single Responsibility** - Each service should have one clear purpose
- **Dependency Injection** - Inject dependencies in constructor
- **Return types** - Always declare return types

```php
// Good ✓
class LoyverseService
{
    public function __construct(
        private HttpClient $client,
        private ApiCredentialRepository $credentials
    ) {}

    public function createOrder(array $orderData): LoyverseOrder
    {
        // Implementation
    }
}
```

#### 3. Models
- **Use relationships** properly
- **Define fillable** or **guarded**
- **Use casts** for JSON and dates
- **Add scopes** for common queries

```php
class Order extends Model
{
    protected $fillable = ['careem_order_id', 'order_data', 'status'];

    protected $casts = [
        'order_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function loyverseOrder()
    {
        return $this->hasOne(LoyverseOrder::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
```

#### 4. Jobs
- **Make jobs idempotent** (safe to run multiple times)
- **Handle failures** properly
- **Add retries** with exponential backoff

```php
class SyncToLoyverseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function handle(LoyverseService $service)
    {
        // Implementation
    }

    public function failed(Throwable $exception)
    {
        // Handle failure
    }
}
```

### Naming Conventions

#### Files & Classes
- **Controllers**: `{Name}Controller.php` (e.g., `OrderController.php`)
- **Models**: Singular, PascalCase (e.g., `Order.php`, `SyncLog.php`)
- **Jobs**: `{Action}{Entity}Job.php` (e.g., `ProcessCareemOrderJob.php`)
- **Services**: `{Name}Service.php` (e.g., `LoyverseService.php`)
- **Repositories**: `{Model}Repository.php` (e.g., `OrderRepository.php`)

#### Methods
- **CRUD operations**: `index`, `store`, `show`, `update`, `destroy`
- **Service methods**: Descriptive verbs (e.g., `createOrder`, `syncToLoyverse`)
- **Boolean methods**: Prefix with `is`, `has`, `can` (e.g., `isActive`, `hasPermission`)

#### Variables
- **camelCase** for variables and properties
- **snake_case** for database columns
- **UPPER_SNAKE_CASE** for constants

### Database Conventions

#### Migrations
- **Naming**: `{timestamp}_create_{table}_table.php`
- **Always reversible**: Implement both `up()` and `down()`
- **Add indexes**: For foreign keys and frequently queried columns

```php
public function up()
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('careem_order_id')->unique();
        $table->json('order_data');
        $table->enum('status', ['pending', 'processing', 'synced', 'failed'])->default('pending');
        $table->timestamps();

        $table->index('status');
        $table->index('created_at');
    });
}
```

### Frontend (Blade + Tailwind + Alpine.js)

#### Blade Templates
- **Use components** for reusable UI elements
- **Use slots** for flexible components
- **Keep logic minimal** in templates

```blade
{{-- Good ✓ --}}
<x-order-card :order="$order" />

{{-- Bad ✗ --}}
{{-- Heavy PHP logic in Blade --}}
```

#### Tailwind CSS
- **Use utility classes** - Avoid custom CSS when possible
- **Use @apply** for repeated patterns (in app.css)
- **Responsive design** - Mobile-first approach

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
    {{-- Responsive grid --}}
</div>
```

#### Alpine.js
- **Keep components small** and focused
- **Use x-data** for component state
- **Use x-init** for initialization

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

## Error Handling

### 1. Try-Catch Blocks
```php
try {
    $result = $this->loyverseService->createOrder($orderData);
    $this->syncLogRepository->logSuccess($order, $result);
} catch (LoyverseApiException $e) {
    $this->syncLogRepository->logFailure($order, $e->getMessage());
    throw $e; // Re-throw for job retry
}
```

### 2. Validation
- Use Form Requests for input validation
- Return clear validation messages
- Validate API responses

### 3. Logging
```php
// Log with context
Log::channel('loyverse')->info('Order synced', [
    'order_id' => $order->id,
    'loyverse_id' => $loyverseOrder->id,
]);

// Log errors with stack trace
Log::error('Sync failed', [
    'order_id' => $order->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

## Testing Requirements

### 1. Unit Tests
- Test individual methods and classes
- Mock external dependencies
- Test edge cases and error scenarios

```php
public function test_loyverse_service_creates_order()
{
    $orderData = ['customer_name' => 'Careem', /* ... */];
    $service = new LoyverseService(/* mocked dependencies */);

    $result = $service->createOrder($orderData);

    $this->assertInstanceOf(LoyverseOrder::class, $result);
}
```

### 2. Feature Tests
- Test complete user flows
- Test API endpoints
- Test webhook processing

```php
public function test_webhook_creates_order_and_queues_sync()
{
    Queue::fake();

    $response = $this->postJson('/api/webhook/careem', $this->validWebhookPayload());

    $response->assertStatus(200);
    Queue::assertPushed(ProcessCareemOrderJob::class);
}
```

### 3. Test Coverage
- Aim for **80%+ coverage** for critical code
- Always test error scenarios
- Test validation rules

## Git Workflow

### Commit Messages
Follow conventional commits format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`

**Examples**:
```
feat(orders): add webhook endpoint for Careem orders

Implement POST /api/webhook/careem endpoint to receive
order notifications from Careem Now platform.

Closes #123
```

```
fix(sync): handle Loyverse API rate limiting

Add exponential backoff when encountering 429 errors
from Loyverse API.
```

### Branch Naming
- **Feature**: `feature/task-description`
- **Bug fix**: `fix/bug-description`
- **Hotfix**: `hotfix/issue-description`

## Security Guidelines

### 1. Input Validation
- **Always validate** all inputs
- **Sanitize** user data before storage
- **Use Form Requests** for validation

### 2. Authentication & Authorization
- **Authenticate** all admin routes
- **Verify webhook** signatures
- **Use middleware** for auth checks

### 3. Sensitive Data
- **Never commit** API keys or secrets
- **Use .env** for configuration
- **Encrypt** sensitive database fields

```php
use Illuminate\Support\Facades\Crypt;

// Encrypt before saving
$apiCredential->credentials = Crypt::encryptString(json_encode($credentials));

// Decrypt when retrieving
$credentials = json_decode(Crypt::decryptString($apiCredential->credentials), true);
```

### 4. SQL Injection Prevention
- **Use Eloquent** or **Query Builder**
- **Never** concatenate user input in queries
- **Use parameter binding** for raw queries

## Performance Optimization

### 1. Database Queries
- **Use eager loading** to prevent N+1 queries
- **Select only needed columns**
- **Add indexes** for frequently queried columns

```php
// Good ✓
$orders = Order::with('loyverseOrder')->select(['id', 'careem_order_id', 'status'])->get();

// Bad ✗
$orders = Order::all(); // Loads all columns
foreach ($orders as $order) {
    $order->loyverseOrder; // N+1 query
}
```

### 2. Caching
- Cache API responses where appropriate
- Cache configuration
- Use Redis for sessions and cache

```php
$credentials = Cache::remember('loyverse_credentials', 3600, function () {
    return ApiCredential::where('service', 'loyverse')->first();
});
```

### 3. Queue Optimization
- Use appropriate queue connections (redis > database)
- Set job timeouts appropriately
- Monitor queue depth

## Documentation Requirements

### 1. Code Comments
- Document **why**, not **what** (code should be self-documenting)
- Add PHPDoc blocks for classes and methods
- Document complex logic

```php
/**
 * Transform Careem order to Loyverse format.
 *
 * @param array $careemOrder Order data from Careem webhook
 * @return array Formatted order data for Loyverse API
 * @throws TransformationException If required fields are missing
 */
public function transform(array $careemOrder): array
{
    // Implementation
}
```

### 2. API Documentation
- Document all API endpoints
- Include request/response examples
- Document error responses

### 3. README Updates
- Update setup instructions if needed
- Document new environment variables
- Update feature list

## CRITICAL: Changelog Update Process

**MANDATORY**: After successfully completing any task, you **MUST** update the changelog.md file.

### How to Update changelog.md

1. **After each successful implementation**, add an entry to changelog.md
2. **Include**: Date, version (if applicable), type, description, files affected
3. **Format**:

```markdown
## [Date: YYYY-MM-DD]

### Added
- Feature name: Brief description
  - Files: List of files created/modified
  - Details: Any important implementation details

### Fixed
- Bug description: What was fixed
  - Files: List of files modified
  - Details: Root cause and solution

### Changed
- What changed: Description
  - Files: List of files modified
  - Details: Why the change was made
```

### Example Changelog Entry

```markdown
## [Date: 2025-10-17]

### Added
- Webhook endpoint for Careem orders
  - Files:
    - app/Http/Controllers/Api/WebhookController.php (created)
    - routes/api.php (modified)
    - app/Http/Requests/CareemOrderRequest.php (created)
  - Details: Implements POST /api/webhook/careem endpoint with signature verification

- Database migrations for core tables
  - Files:
    - database/migrations/2025_10_17_000001_create_orders_table.php (created)
    - database/migrations/2025_10_17_000002_create_loyverse_orders_table.php (created)
  - Details: Created orders, loyverse_orders, sync_logs, webhook_logs, and api_credentials tables
```

### When to Update Changelog

Update the changelog IMMEDIATELY after:
- Creating new features or functionality
- Fixing bugs
- Refactoring code
- Adding tests
- Updating documentation (for major changes)
- Modifying configuration
- Adding/removing dependencies

### What NOT to Log
- Trivial changes (typo fixes in comments)
- Work-in-progress commits
- Temporary debugging code

## Pre-Submission Checklist

Before considering a task complete, verify:

- [ ] Code follows Laravel best practices
- [ ] All naming conventions are followed
- [ ] Error handling is implemented
- [ ] Logging is added for important operations
- [ ] Tests are written and passing
- [ ] No sensitive data is committed
- [ ] Code is properly documented
- [ ] **changelog.md is updated** ← **CRITICAL**
- [ ] No console.log or debug statements remain
- [ ] Code is formatted consistently

## Common Pitfalls to Avoid

### ❌ Don't
- Hardcode credentials or sensitive data
- Put business logic in controllers
- Forget to handle exceptions
- Skip writing tests
- Commit .env file
- Use raw SQL queries unnecessarily
- Forget to add database indexes
- **Forget to update changelog.md**
- Leave TODO comments without creating tasks

### ✓ Do
- Use environment variables for configuration
- Keep controllers thin, services fat
- Handle all exception scenarios
- Write tests for new features
- Use .env.example for documentation
- Use Eloquent/Query Builder
- Index foreign keys and frequently queried columns
- **Always update changelog.md after successful execution**
- Convert TODOs to tasks in Tasks.md

## Getting Help

### When Stuck
1. Review Context.md for project understanding
2. Check Tasks.md for task dependencies
3. Review changelog.md for similar implementations
4. Check Laravel documentation
5. Review Loyverse API documentation

### Useful Resources
- Laravel Documentation: https://laravel.com/docs
- Loyverse API: https://developer.loyverse.com/
- Tailwind CSS: https://tailwindcss.com/docs
- Alpine.js: https://alpinejs.dev/

## Environment Variables Template

When creating features that need configuration, add to `.env.example`:

```env
# Loyverse Configuration
LOYVERSE_API_URL=https://api.loyverse.com/v1
LOYVERSE_API_KEY=
LOYVERSE_STORE_ID=

# Careem Configuration
CAREEM_WEBHOOK_SECRET=

# Queue Configuration
QUEUE_CONNECTION=redis
```

## Final Reminder

**Every successful task completion MUST include a changelog.md update.**

This is not optional. The changelog serves as:
- Project history
- Documentation of implementations
- Reference for future development
- Audit trail for changes

Failure to update the changelog means the task is **incomplete**.

---

**Good luck with your implementation! Follow these guidelines, write clean code, and keep the changelog updated.**
