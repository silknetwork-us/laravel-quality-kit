<?php

declare(strict_types=1);

namespace SilkNetwork;

use Illuminate\Support\ServiceProvider;

class LaravelQualityKitProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // This method is intentionally left empty
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish the PHPStan configuration
        $this->publishes([
            __DIR__.'/../config/phpstan.neon' => base_path('phpstan.neon'),
        ], 'phpstan-config');

        $this->publishes([
            __DIR__.'/../config/rector.php' => base_path('rector.php'),
        ], 'rector-config');

        $this->publishes([
            __DIR__.'/../config/pint.json' => base_path('pint.json'),
        ], 'pint-config');

        $this->publishes([
            __DIR__.'/../config/peck.json' => base_path('peck.json'),
        ], 'peck-config');

        // Register commands to generate IDE helper files
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\GenerateIdeHelperCommand::class,
                Console\Commands\BootstrapCommand::class,
            ]);
        }
    }
}
