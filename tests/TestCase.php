<?php

namespace AzahariZaman\BackOffice\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use AzahariZaman\BackOffice\BackOfficeServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        $this->artisan('migrate');
    }

    protected function getPackageProviders($app)
    {
        return [
            BackOfficeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}