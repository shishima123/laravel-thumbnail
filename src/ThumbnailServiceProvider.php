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
        if ($this->app->runningInConsole()) {

        }
        $this->publishes([
            __DIR__ . '/config/thumbnail.php' => config_path('thumbnail.php'),
        ], 'thumbnail-config');

        $this->publishes([
            __DIR__.'/public' => public_path('vendor/thumbnail'),
        ], 'thumbnail-public');

        // Register the new disk configuration to filesystems
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
            __DIR__ . '/config/thumbnail.php', 'thumbnail'
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
}
