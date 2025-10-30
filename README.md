# Backoffice Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azaharizaman/backoffice.svg?style=flat-square)](https://packagist.org/packages/azaharizaman/backoffice)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/azaharizaman/backoffice/run-tests?label=tests)](https://github.com/azaharizaman/backoffice/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/azaharizaman/backoffice/Check%20&%20fix%20styling?label=code%20style)](https://github.com/azaharizaman/backoffice/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/azaharizaman/backoffice.svg?style=flat-square)](https://packagist.org/packages/azaharizaman/backoffice)

A comprehensive Laravel package for managing hierarchical company structures, offices, departments, staff, and organizational units. This package provides a complete backend solution for complex organizational management without any UI components.

## Features

### Hierarchical Company Structure
- **Parent-Child Companies**: Support for multi-level company hierarchies
- **Office Management**: Physical office structures with hierarchical relationships
- **Department Management**: Logical department hierarchies
- **Staff Management**: Employee assignment to offices and/or departments
- **Unit Management**: Logical staff groupings with unit group organization

### Key Capabilities
- **Multi-Hierarchy Support**: Both physical (offices) and logical (departments) hierarchies
- **Flexible Staff Assignment**: Staff can belong to offices, departments, or both
- **Unit Organization**: Staff can belong to multiple units within unit groups
- **Office Types**: Configurable office type categorization
- **Comprehensive Policies**: Built-in authorization policies
- **Observer Patterns**: Automatic event handling for data changes
- **Console Commands**: Management utilities via Artisan commands

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+

## Installation

You can install the package via composer:

```bash
composer require azaharizaman/backoffice
```

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="AzahariZaman\BackOffice\BackOfficeServiceProvider" --tag="backoffice-migrations"
php artisan migrate
```

Optionally, you can publish the config file:

```bash
php artisan vendor:publish --provider="AzahariZaman\BackOffice\BackOfficeServiceProvider" --tag="backoffice-config"
```

## Quick Start

See the [documentation](docs/README.md) for detailed usage instructions.

## Documentation

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Models & Relationships](docs/models.md)
- [Traits & Behaviors](docs/traits.md)
- [Policies & Authorization](docs/policies.md)
- [Console Commands](docs/commands.md)
- [API Reference](docs/api.md)
- [Examples](docs/examples.md)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Azahari Zaman](https://github.com/azaharizaman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.