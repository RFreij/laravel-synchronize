<?php

namespace LaravelSynchronize\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LaravelSynchronize\Console\Synchronizer\Synchronizer;
use SplFileInfo;

class SynchronizeCommand extends Command
{
    /**
     * The Synchronizer instance.
     *
     * @var \LaravelSynchronize\Console\Synchronizer\Synchronizer
     */
    protected $synchronizer;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-sync:synchronize {--class=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute synchronizations that have not been executed yet';

    /**
     * Create a new controller creator command instance.
     *
     * @param  \LaravelSynchronize\Console\Synchronizer\Synchronizer  $synchronizer
     *
     * @return void
     */
    public function __construct(Synchronizer $synchronizer)
    {
        parent::__construct();

        $this->synchronizer = $synchronizer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $forced = $this->option('force');
        $class = $this->option('class');
        $files = $this->synchronizer->getSynchronizations();

        $fileNames = $files->map(function ($file) {
            return $file->getFileName();
        });

        if ($forced) {
            $filesToHandle = $files->filter(function ($file) use ($class) {
                return !$class || ($class && $class === $this->getClassName($file));
            });
        } else {
            $handledFiles = DB::table(config('synchronizer.table'))->pluck('synchronization');
            $unHandledFiles = $fileNames->diff($handledFiles);

            $filesToHandle = $files->filter(function ($file) use ($unHandledFiles, $class) {
                return $unHandledFiles->contains($file->getFileName())
                    && (!$class || ($class === $this->getClassName($file)));
            });
        }

        if ($filesToHandle->isEmpty()) {
            $this->info('No synchronizations found.');

            return;
        }

        $filesToHandle->each(function ($file) {
            $this->info('Synchronising ' . $file->getFileName());

            $this->synchronizer->run($file);

            $this->info('Synchronized ' . $file->getFileName());
        });

        $this->info('Synchronizations completed');
    }

    /**
     * Get class name for file
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function getClassName(SplFileInfo $file)
    {
        return $this->synchronizer->getClassName(
            $this->synchronizer->getSynchronizationName($file->getFilename())
        );
    }
}
