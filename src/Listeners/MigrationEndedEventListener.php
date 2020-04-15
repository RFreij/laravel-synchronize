<?php

namespace LaravelSynchronize\Listeners;

use Illuminate\Database\Events\MigrationEnded;
use LaravelSynchronize\Console\Synchronizer\Synchronizer;
use LaravelSynchronize\Console\Synchronizer\SynchronizerRepository;

class MigrationEndedEventListener
{
    /**
     * The Synchronizer instance.
     *
     * @var \LaravelSynchronize\Console\Synchronizer\Synchronizer
     */
    protected $synchronizer;

    /**
     * @var \LaravelSynchronize\Console\Synchronizer\SynchronizerRepository $synchronizerRepository
     */
    protected $synchronizerRepository;

    public function __construct(Synchronizer $synchronizer, SynchronizerRepository $synchronizerRepository)
    {
        $this->synchronizer = $synchronizer;
        $this->synchronizerRepository = $synchronizerRepository;
    }

    /**
     * Handle the event.
     *
     * @todo use MigrationsStarted event to collect synchronizations
     *
     * @param MigrationEnded $migrationEnded
     *
     * @return void
     *
     * @author Ramon Bakker <ramon@bsbip.com>
     * @version 1.0.0
     */
    public function handle(MigrationEnded $migrationEnded)
    {
        // Synchronizations should execute after going down
        if ($migrationEnded->method !== 'down') {
            return;
        }

        $class = get_class($migrationEnded->migration) . 'Synchronization';
        $files = $this->synchronizer->getSynchronizations();

        $handledFiles = collect($this->synchronizerRepository->getLast())
            ->pluck('synchronization');

        $filesToHandle = $files->filter(function ($file) use ($handledFiles) {
            return $handledFiles->contains($file->getFileName());
        });

        if ($filesToHandle->isEmpty()) {
            echo "No synchronization found for {$class}\n";

            return;
        }

        $filesToHandle->each(function ($file) {
            echo 'Rolling back synchronization: ' . $file->getFileName() . "\n";

            $this->synchronizer->run($file, 'down');

            echo 'Rolled back synchronization: ' . $file->getFileName() . "\n";
        });
    }
}
