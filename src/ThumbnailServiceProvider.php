<?php

namespace PhuocNguyen\Thumbnail;

use Illuminate\Support\ServiceProvider;

class ThumbnailServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishing();

        if (Thumbnail::$runsMigrations && $this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Register the new disks configuration to filesystems
        app()->config["filesystems.disks"] = array_merge(config('filesystems.disks'), config('thumbnail.disks'));
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/thumbnail.php',
            'thumbnail'
        );

        $this->app->singleton(Thumbnail::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [Thumbnail::class];
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/thumbnail.php' => config_path('thumbnail.php'),
            ], 'thumbnail-config');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/laravel_thumbnail'),
            ], 'laravel-assets');

            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__ . '/../database/migrations/create_thumbnail_table.php.stub' => database_path(
                    "/migrations/{$timestamp}_create_thumbnail_table_table.php"
                ),
            ], 'thumbnail-migrations');
        }
    }
}
