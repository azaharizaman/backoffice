<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\Unit;
use AzahariZaman\BackOffice\Models\UnitGroup;
use AzahariZaman\BackOffice\Models\OfficeType;
use AzahariZaman\BackOffice\Observers\CompanyObserver;
use AzahariZaman\BackOffice\Observers\OfficeObserver;
use AzahariZaman\BackOffice\Observers\DepartmentObserver;
use AzahariZaman\BackOffice\Observers\StaffObserver;
use AzahariZaman\BackOffice\Policies\CompanyPolicy;
use AzahariZaman\BackOffice\Policies\OfficePolicy;
use AzahariZaman\BackOffice\Policies\DepartmentPolicy;
use AzahariZaman\BackOffice\Policies\StaffPolicy;
use AzahariZaman\BackOffice\Commands\InstallBackOfficeCommand;
use AzahariZaman\BackOffice\Commands\CreateOfficeTypesCommand;
use AzahariZaman\BackOffice\Commands\ProcessResignationsCommand;

/**
 * BackOffice Service Provider
 * 
 * Registers all package components including models, observers, policies,
 * commands, and configuration.
 */
class BackOfficeServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     */
    public array $bindings = [];

    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/backoffice.php',
            'backoffice'
        );

        // Register services
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register migrations
        $this->registerMigrations();

        // Register configuration
        $this->registerConfiguration();

        // Register commands
        $this->registerCommands();

        // Register observers
        $this->registerObservers();

        // Register policies
        $this->registerPolicies();

        // Register publishables
        $this->registerPublishables();
    }

    /**
     * Register package services.
     */
    protected function registerServices(): void
    {
        // Register any package services here
    }

    /**
     * Register package migrations.
     */
    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Register package configuration.
     */
    protected function registerConfiguration(): void
    {
        // Configuration is already registered in the register method
    }

    /**
     * Register package commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallBackOfficeCommand::class,
                CreateOfficeTypesCommand::class,
                ProcessResignationsCommand::class,
            ]);
        }
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        Company::observe(CompanyObserver::class);
        Office::observe(OfficeObserver::class);
        Department::observe(DepartmentObserver::class);
        Staff::observe(StaffObserver::class);
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
    }

    /**
     * Register publishable assets.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/backoffice.php' => config_path('backoffice.php'),
            ], 'backoffice-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'backoffice-migrations');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            // Add any services that this provider provides
        ];
    }
}