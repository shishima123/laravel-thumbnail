<?php

namespace Shishima\Thumbnail\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Shishima\Thumbnail\ThumbnailServiceProvider;
use Illuminate\Contracts\Config\Repository;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ThumbnailServiceProvider::class
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../tests/database');
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app->make('config'), function (Repository $config)
        {
            $config->set('thumbnail.disks.temp_thumbnail.root', storage_path('framework/testing/disks/temp_thumbnail'));
            $config->set('thumbnail.disks.thumbnail.root', storage_path('framework/testing/disks/thumbnail'));
            $config->set('thumbnail.disks.thumbnail.url', storage_path('framework/testing/disks/thumbnail'));

            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }
}
