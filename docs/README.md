# BackOffice Package Documentation

Welcome to the comprehensive documentation for the BackOffice Laravel package. This package provides a complete backend solution for managing hierarchical company structures, offices, departments, staff, and organizational units.

## Table of Contents

- [Installation Guide](installation.md)
- [Configuration](configuration.md)
- [Models & Relationships](models.md)
- [Organizational Chart & Reporting Lines](organizational-chart.md)
- [Staff Resignation Management](resignation.md)
- [Staff Transfer System](staff-transfers.md)
- [Traits & Behaviors](traits.md)
- [Policies & Authorization](policies.md)
- [Console Commands](commands.md)
- [API Reference](api.md)
- [Examples](examples.md)
- [Best Practices](best-practices.md)
- [Troubleshooting](troubleshooting.md)

## Overview

The BackOffice package is designed to handle complex organizational structures with the following key features:

### Hierarchical Structures
- **Companies**: Parent-child company relationships
- **Offices**: Physical office hierarchies with unlimited depth
- **Departments**: Logical department hierarchies
- **Units**: Flat organizational units grouped by unit groups

### Staff Management
- Staff can belong to offices and/or departments
- Multiple unit assignments per staff member
- Comprehensive staff information tracking
- **Organizational Chart & Reporting Lines**:
  - Hierarchical supervisor/subordinate relationships
  - Comprehensive organizational chart generation
  - Reporting path analysis and statistics
  - Multiple export formats (JSON, CSV, DOT/Graphviz)
  - Reorganization suggestions and analytics
- **Staff Resignation Management**:
  - Schedule resignations with future dates
  - Automatic resignation processing
  - Resignation reason tracking
  - Resignation cancellation support
- **Staff Transfer System**:
  - Transfer staff between offices and departments
  - Approval workflow with status tracking
  - Scheduled transfers with effective dates
  - Complete audit trail and history
  - Transfer validation and business rules

### Flexible Architecture
- Model traits for reusable functionality
- Observer pattern for automatic event handling
- Policy-based authorization
- Configurable validation rules
- Extensible through custom models

## Quick Start

1. **Install the package**:
```bash
composer require azaharizaman/backoffice
```

2. **Install the package components**:
```bash
php artisan backoffice:install
```

3. **Create your first company**:
```php
use Carbon\Carbon;

$company = Company::create([
    'name' => 'My Company',
    'code' => 'MYCO',
    'description' => 'My company description',
    'is_active' => true,
]);
```

4. **Create offices and departments**:
```php
// Create main office
$mainOffice = $company->offices()->create([
    'name' => 'Head Office',
    'code' => 'HO',
    'address' => '123 Main Street',
    'is_active' => true,
]);

// Create department
$department = $company->departments()->create([
    'name' => 'Human Resources',
    'code' => 'HR',
    'is_active' => true,
]);
```

5. **Add staff**:
```php
$staff = Staff::create([
    'employee_id' => 'EMP001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@company.com',
    'office_id' => $mainOffice->id,
    'department_id' => $department->id,
    'position' => 'HR Manager',
    'hire_date' => now(),
    'status' => StaffStatus::ACTIVE,
    'is_active' => true,
]);
```

6. **Manage staff resignations**:
```php
use AzahariZaman\BackOffice\Models\Company;

// Schedule resignation 30 days from now
$staff->scheduleResignation(
    Carbon::now()->addDays(30),
    'Found better opportunity'
);

// Process resignations automatically
php artisan backoffice:process-resignations --force
```

## Key Concepts

### Hierarchy Management
The package provides robust hierarchy management through the `HasHierarchy` trait, which offers:
- Ancestor/descendant traversal
- Root/leaf identification
- Circular reference prevention
- Path calculation

### Flexible Assignment
Staff can be assigned to:
- Office only
- Department only
- Both office and department
- Multiple units across different unit groups

### Event-Driven Architecture
All models implement observer patterns for:
- Validation on creation/update
- Automatic cleanup on deletion
- Hierarchy integrity maintenance
- Custom business logic hooks

## Support

For issues, feature requests, or questions, please:
1. Check the [troubleshooting guide](troubleshooting.md)
2. Review the [examples](examples.md)
3. Open an issue on GitHub

## Contributing

Please see [CONTRIBUTING.md](../CONTRIBUTING.md) for guidelines on contributing to this package.