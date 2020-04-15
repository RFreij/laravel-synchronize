<?php

namespace LaravelSynchronize\Providers;

use LaravelSynchronize\Console\Commands\SynchronizeCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use LaravelSynchronize\Console\Commands\MakeSynchronizationCommand;

/**
 * Service provider
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishments();

        $this->bootCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->getDefaultConfigFilePath('synchronizer'),
            'synchronizer'
        );

        $this->app->register(LaravelSynchronizeEventServiceProvider::class);
    }

    /**
     * Register publishments
     *
     * @return void
     */
    protected function registerPublishments()
    {
        $this->publishes([
            __DIR__ . '/../config/synchronizer.php' => config_path('synchronizer.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * Get default configuration file path
     *
     * @return string
     */
    public function getDefaultConfigFilePath($configName)
    {
        return realpath(__DIR__ . "/../config/{$configName}.php");
    }

    /**
     * Boot commands
     *
     * @return void
     */
    private function bootCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeSynchronizationCommand::class,
                SynchronizeCommand::class,
            ]);
        }
    }
}
