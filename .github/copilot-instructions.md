# GitHub Copilot Instructions

## Project Overview
This is the BackOffice Laravel package - a comprehensive library for managing hierarchical organizational structures including companies, offices, departments, staff, units, and **staff transfers**. This package provides complete backend functionality for complex organizational management without UI components.

## Architecture & Design Patterns

### Package Structure
- **Models**: Eloquent models with hierarchical relationships and observer patterns
- **Policies**: Authorization logic for all major entities
- **Observers**: Automatic event handling for model lifecycle events
- **Commands**: Artisan console commands for package management and batch processing
- **Traits**: Reusable behaviors like `HasHierarchy` for hierarchical models
- **Helpers**: Utility classes like `OrganizationalChart` and `StaffTransferHelper` for complex operations
- **Casts**: Custom attribute casting for complex data types
- **Enums**: Type-safe constants for status values and categorization
- **Exceptions**: Custom business logic exceptions with factory methods

### Key Design Principles
- **Hierarchy First**: Support unlimited depth for offices and departments
- **Flexible Relationships**: Staff can belong to offices and/or departments
- **Observer Pattern**: Automatic event handling for all model changes
- **Policy-Driven**: Comprehensive authorization for all operations
- **Trait-Based**: Reusable functionality through well-designed traits
- **Type Safety**: PHP 8.2+ features with strict typing and enums
- **Status Workflows**: Enum-driven state machines with validation

## Code Style & Standards

### PHP Standards
- **PHP Version**: 8.2+ with strict types (`declare(strict_types=1);`)
- **PSR-12**: Follow PSR-12 coding standards
- **Type Hints**: Always use strict type hints for parameters and return types
- **Null Safety**: Use nullable types (`?Type`) when appropriate
- **Documentation**: Comprehensive PHPDoc for all public methods and properties

### Laravel Conventions
- **Eloquent Models**: Follow Laravel naming conventions (singular class names, snake_case table names)
- **Relationships**: Use proper relationship methods with explicit foreign keys
- **Scopes**: Implement query scopes for common filtering operations
- **Mutators/Accessors**: Use Laravel 9+ attribute casting and accessors
- **Validation**: Use form request validation or model validation rules

### Naming Conventions
```php
// Models: Singular PascalCase
class Staff extends Model

// Tables: Plural snake_case
protected $table = 'backoffice_staff';

// Columns: snake_case
public string $employee_id;
public string $first_name;

// Methods: camelCase with descriptive names
public function getFullNameAttribute(): string
public function getReportingPath(): Collection
public function getAllStaff(): Collection

// Constants: SCREAMING_SNAKE_CASE
public const DEFAULT_STATUS = 'active';

// Enums: PascalCase with descriptive values
enum StaffStatus: string {
    case ACTIVE = 'active';
    case RESIGNED = 'resigned';
}
```

## Model Development Guidelines

### Hierarchical Models
When working with hierarchical models (Company, Office, Department):

```php
// Always include hierarchy management
use AzahariZaman\BackOffice\Traits\HasHierarchy;

class Office extends Model
{
    use HasHierarchy;
    
    // Define parent relationship
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'parent_id');
    }
    
    // Define children relationship
    public function children(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_id');
    }
    
    // Implement required abstract methods
    public function getParentKey(): string
    {
        return 'parent_id';
    }
}
```

### Staff Relationships
Staff models have complex relationships:

```php
// Supervisor/subordinate relationships
public function supervisor(): BelongsTo
{
    return $this->belongsTo(Staff::class, 'supervisor_id');
}

public function subordinates(): HasMany
{
    return $this->hasMany(Staff::class, 'supervisor_id');
}

// Office and department assignments
public function office(): BelongsTo
{
    return $this->belongsTo(Office::class);
}

public function department(): BelongsTo
{
    return $this->belongsTo(Department::class);
}

// Unit assignments (many-to-many)
public function units(): BelongsToMany
{
    return $this->belongsToMany(Unit::class, 'backoffice_staff_unit');
}

// Staff Transfer relationships
public function transfers(): HasMany
{
    return $this->hasMany(StaffTransfer::class);
}

public function hasActiveTransfer(): bool
{
    return $this->transfers()
        ->whereIn('status', [StaffTransferStatus::PENDING, StaffTransferStatus::APPROVED])
        ->exists();
}
```

### Staff Transfer System Architecture
The Staff Transfer system uses a status-driven workflow with automatic processing:

```php
// Status-driven model with enum validation
class StaffTransfer extends Model
{
    protected $casts = [
        'status' => StaffTransferStatus::class,
        'effective_date' => 'date',
    ];
    
    // Automatic validation on status changes
    public function approve(Staff $approvedBy, ?string $notes = null): void
    {
        if (!$this->status->canBeModified()) {
            throw new InvalidTransferException('Transfer cannot be approved in current status: ' . $this->status->value);
        }
        // ... approval logic
    }
}

// Observer handles automatic processing
class StaffTransferObserver
{
    public function created(StaffTransfer $transfer): void
    {
        // Immediate transfers auto-complete
        if ($transfer->isImmediate()) {
            $this->processTransfer($transfer);
        }
    }
}
```

### Query Scopes
Implement consistent query scopes:

```php
// Status scopes
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}

public function scopeByStatus(Builder $query, StaffStatus $status): Builder
{
    return $query->where('status', $status);
}

// Hierarchy scopes
public function scopeTopLevel(Builder $query): Builder
{
    return $query->whereNull('parent_id');
}

public function scopeManagers(Builder $query): Builder
{
    return $query->whereHas('subordinates');
}

// Transfer-specific scopes
public function scopePending(Builder $query): Builder
{
    return $query->where('status', StaffTransferStatus::PENDING);
}

public function scopeApproved(Builder $query): Builder
{
    return $query->where('status', StaffTransferStatus::APPROVED);
}

public function scopeDue(Builder $query): Builder
{
    return $query->approved()->where('effective_date', '<=', now());
}
```

## Testing Guidelines

### Test Structure
- **Feature Tests**: Test complete workflows and integrations
- **Unit Tests**: Test individual methods and behaviors
- **Observer Tests**: Test automatic event handling
- **Policy Tests**: Test authorization logic

### Test Naming
```php
// Feature tests: descriptive scenarios
public function test_it_can_generate_organizational_chart_for_company(): void
public function test_staff_resignation_updates_supervisor_assignments(): void

// Unit tests: specific behaviors
public function test_get_ancestors_returns_all_supervisors(): void
public function test_circular_reference_validation_prevents_invalid_assignments(): void
```

### Test Data Setup
```php
// Use proper model factories
protected function setUp(): void
{
    parent::setUp();
    
    $this->company = Company::factory()->create();
    $this->office = Office::factory()->for($this->company)->create();
    $this->department = Department::factory()->for($this->company)->create();
}

// Create realistic hierarchies
$ceo = Staff::factory()->create(['position' => 'CEO']);
$manager = Staff::factory()->create(['supervisor_id' => $ceo->id]);
$employee = Staff::factory()->create(['supervisor_id' => $manager->id]);

// For transfer testing, use manual creation (no factories available)
$transfer = StaffTransfer::create([
    'staff_id' => $staff->id,
    'from_office_id' => $sourceOffice->id,
    'to_office_id' => $targetOffice->id,
    'effective_date' => now()->addWeek(),
    'status' => StaffTransferStatus::PENDING,
    'requested_by_id' => $hrStaff->id,
    'is_immediate' => false,
]);
```

## Database Design Patterns

### Migration Standards
```php
// Always include proper foreign key constraints
$table->foreignId('company_id')->constrained('backoffice_companies');
$table->foreignId('supervisor_id')->nullable()->constrained('backoffice_staff');

// Use consistent table naming
Schema::create('backoffice_staff_unit', function (Blueprint $table) {
    // Pivot table for many-to-many relationships
});

// Include proper indexes
$table->index(['company_id', 'is_active']);
$table->index(['supervisor_id', 'status']);
```

### Validation Rules
```php
// Model validation
protected function rules(): array
{
    return [
        'employee_id' => ['required', 'string', 'max:50', 'unique:backoffice_staff'],
        'first_name' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'unique:backoffice_staff'],
        'supervisor_id' => ['nullable', 'exists:backoffice_staff,id'],
    ];
}

// Custom validation for business rules
public function validateSupervisorAssignment(Staff $supervisor): void
{
    if ($this->wouldCreateCircularReference($supervisor)) {
        throw new InvalidAssignmentException('Cannot set supervisor: would create circular reference');
    }
}
```

## Helper and Service Classes

### OrganizationalChart Helper
When extending organizational chart functionality:

```php
// Return consistent data structures
public static function forCompany(Company $company): array
{
    return [
        'company' => $company->only(['id', 'name', 'code']),
        'chart' => static::buildHierarchy($company->getTopLevelStaff()),
        'metadata' => [
            'total_staff' => $company->getAllStaff()->count(),
            'generated_at' => now()->toISOString(),
        ],
    ];
}

// Support multiple export formats
public static function export(Company $company, string $format = 'json'): string|array
{
    return match ($format) {
        'csv' => static::exportToCsv($company),
        'dot' => static::exportToDot($company),
        default => static::forCompany($company),
    };
}
```

## Console Commands

### Command Structure
```php
// Descriptive signatures
protected $signature = 'backoffice:process-resignations 
                       {--dry-run : Show what would be processed without making changes}
                       {--date= : Process resignations for specific date}';

// Comprehensive help text
protected $description = 'Process scheduled staff resignations and update organizational structure';

// Proper error handling and output
public function handle(): int
{
    try {
        $this->info('Processing resignations...');
        $results = $this->processResignations();
        $this->table(['Employee ID', 'Name', 'Status'], $results);
        return self::SUCCESS;
    } catch (\Exception $e) {
        $this->error('Failed to process resignations: ' . $e->getMessage());
        return self::FAILURE;
    }
}
```

## Package Development Standards

### Service Provider
```php
// Register all package components
public function register(): void
{
    $this->mergeConfigFrom(__DIR__.'/../config/backoffice.php', 'backoffice');
    $this->registerPolicies();
}

public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    $this->loadTranslationsFrom(__DIR__.'/../lang', 'backoffice');
    $this->publishResources();
    $this->registerObservers();
    $this->registerCommands();
}
```

### Configuration
```php
// Use descriptive configuration keys
return [
    'table_prefix' => 'backoffice_',
    'enable_observers' => true,
    'resignation_processing' => [
        'enabled' => true,
        'batch_size' => 100,
    ],
    'organizational_chart' => [
        'max_depth' => 10,
        'cache_ttl' => 3600,
    ],
];
```

## Error Handling & Validation

### Custom Exceptions
```php
// Create specific exceptions for business logic
class CircularReferenceException extends \InvalidArgumentException
{
    public static function forStaff(Staff $staff, Staff $supervisor): self
    {
        return new self(
            "Cannot assign {$supervisor->full_name} as supervisor for {$staff->full_name}: would create circular reference"
        );
    }
}
```

### Validation Patterns
```php
// Validate business rules at model level
protected static function booted(): void
{
    static::creating(function (Staff $staff) {
        $staff->validateBusinessRules();
    });
    
    static::updating(function (Staff $staff) {
        if ($staff->isDirty('supervisor_id')) {
            $staff->validateSupervisorChange();
        }
    });
}
```

## Documentation Standards

### Method Documentation
```php
/**
 * Generate comprehensive organizational chart for a company.
 * 
 * @param Company $company The company to generate chart for
 * @return array{
 *     company: array{id: int, name: string, code: string},
 *     chart: array<int, array>,
 *     metadata: array{total_staff: int, generated_at: string}
 * }
 * 
 * @throws \InvalidArgumentException When company has no staff
 * 
 * @example
 * $chart = OrganizationalChart::forCompany($company);
 * echo "Total staff: " . $chart['metadata']['total_staff'];
 */
public static function forCompany(Company $company): array
```

### Class Documentation
```php
/**
 * Staff model representing employees in the organizational hierarchy.
 * 
 * Supports supervisor/subordinate relationships, multiple unit assignments,
 * and comprehensive organizational chart generation.
 * 
 * @property int $id
 * @property string $employee_id Unique employee identifier
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name Computed full name attribute
 * @property StaffStatus $status Current employment status
 * @property-read Collection<Staff> $subordinates Direct reports
 * @property-read Collection<Staff> $ancestors All supervisors up the chain
 * 
 * @method static Builder active() Only active staff
 * @method static Builder managers() Staff with subordinates
 * @method static Builder atLevel(int $level) Staff at specific reporting level
 */
class Staff extends Model
```

## Common Patterns to Follow

1. **Always validate hierarchical relationships** to prevent circular references
2. **Use observers for automatic updates** when organizational structure changes
3. **Implement proper authorization** using policies for all operations
4. **Cache expensive operations** like organizational chart generation
5. **Provide multiple export formats** for integration with external systems
6. **Use descriptive method names** that clearly indicate functionality
7. **Handle edge cases gracefully** with proper error messages
8. **Write comprehensive tests** for all business logic
9. **Document complex algorithms** with clear examples
10. **Follow Laravel conventions** for naming and structure

