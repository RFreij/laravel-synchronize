<?php

namespace LaravelSynchronize\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

/**
 * Event service provider
 */
class LaravelSynchronizeEventServiceProvider extends EventServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Database\Events\MigrationStarted' => [
            'LaravelSynchronize\Listeners\MigrationStartedEventListener',
        ],
        'Illuminate\Database\Events\MigrationEnded' => [
            'LaravelSynchronize\Listeners\MigrationEndedEventListener',
        ],
    ];
}
