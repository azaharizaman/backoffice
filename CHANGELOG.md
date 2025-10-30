# Changelog

All notable changes to `backoffice` will be documented in this file.

## Unreleased

### Added
- **Model Factories** - Comprehensive factory classes for all models
  - CompanyFactory with states: active, inactive, childOf, root
  - OfficeFactory with states: active, inactive, childOf, root
  - DepartmentFactory with states: active, inactive, childOf, root
  - StaffFactory with 14+ states including resigned, onProbation, manager, ceo
  - UnitFactory and UnitGroupFactory with active/inactive states
  - OfficeTypeFactory with active/inactive states
  - All factories use realistic fake data via Faker
  - Support for hierarchical relationships and complex object graphs
- **Factory Documentation** - Comprehensive guide in docs/factories.md
  - Usage examples for all factories
  - Best practices and patterns
  - Seeding examples
  - Testing examples
- **Updated Copilot Instructions** - Added model factory requirements and guidelines

### Changed
- **All Tests Updated** - Converted all tests to use model factories instead of manual creation
  - Feature tests: CompanyTest, OfficeTest, DepartmentTest, StaffTest, and others
  - Unit tests: CompanyObserverTest, HasHierarchyTraitTest, EnumsTest
  - Simplified test setup code by 30-40% in many cases
  - Improved test readability with expressive factory states
- **Model newFactory() Methods** - Added factory registration to all 8 models

### Documentation
- Added docs/factories.md with comprehensive factory documentation
- Updated docs/README.md to reference factory documentation
- Updated .github/copilot-instructions.md with factory best practices
- Updated Quick Start guide to demonstrate factory usage

## 1.0.0 - 2025-10-30

- Initial release
- Company hierarchy management
- Office hierarchy management
- Department hierarchy management
- Staff management with office/department assignment
- Unit and unit group management
- Comprehensive model relationships
- Policy-based authorization
- Observer pattern implementation
- Console commands for management
- Full documentation suite