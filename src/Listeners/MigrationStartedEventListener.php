<?php

namespace LaravelSynchronize\Listeners;

use Schema;
use SplFileInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Events\MigrationStarted;
use LaravelSynchronize\Console\Synchronizer\Synchronizer;

class MigrationStartedEventListener
{
    /**
     * The Synchronizer instance.
     *
     * @var \LaravelSynchronize\Console\Synchronizer\Synchronizer
     */
    protected $synchronizer;

    public function __construct(Synchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;
    }

    /**
     * Handle the event.
     *
     * @todo use MigrationsStarted event to collect synchronization files
     *
     * @param MigrationStarted $migrationStarted
     *
     * @return void
     *
     * @author Ramon Bakker <ramon@bsbip.com>
     * @version 1.0.0
     */
    public function handle(MigrationStarted $migrationStarted)
    {
        // Synchronizations should execute before going up
        if ($migrationStarted->method !== 'up') {
            return;
        }

        if (!Schema::hasTable(Config::get('synchronizer.table'))) {
            return;
        }

        $class = get_class($migrationStarted->migration) . 'Synchronization';
        $files = $this->synchronizer->getSynchronizations();

        $fileNames = $files->map(function ($file) {
            return $file->getFileName();
        });

        $handledFiles = DB::table(Config::get('synchronizer.table'))->pluck('synchronization');
        $unHandledFiles = $fileNames->diff($handledFiles);

        $filesToHandle = $files->filter(function ($file) use ($unHandledFiles, $class) {
            return $unHandledFiles->contains($file->getFileName())
                && (!$class || ($class === $this->getClassName($file)));
        });

        if ($filesToHandle->isEmpty()) {
            echo "No synchronization found for {$class}\n";

            return;
        }

        $filesToHandle->each(function ($file) {
            echo 'Synchronizing: ' . $file->getFileName() . "\n";

            $this->synchronizer->run($file, 'up');

            echo 'Synchronized: ' . $file->getFileName() . "\n";
        });
    }

    /**
     * Get class name for file
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function getClassName(SplFileInfo $file): string
    {
        return $this->synchronizer->getClassName(
            $this->synchronizer->getSynchronizationName($file->getFilename())
        );
    }
}
