<?php

namespace LaravelSynchronize\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LaravelSynchronize\Console\Synchronizer\Synchronizer;

class SynchronizeCommand extends Command
{
    /**
     * The Synchronizer instance.
     *
     * @var \LaravelSynchronize\Synchronizations\Synchronizer
     */
    protected $synchronizer;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-sync:synchronize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute synchronizations that have not been executed yet';

    /**
     * Create a new controller creator command instance.
     *
     * @param  \LaravelSynchronize\Synchronizations\Synchronizer  $synchronizer
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
        $files = collect($this->files->files($this->directory));
        $fileNames = $files->map(function ($file, $index) {
            return $file->getFileName();
        });

        $handledFiles = DB::table('synchronizations')->pluck('synchronization');
        $unHandledFiles = $fileNames->diff($handledFiles);

        if ($unHandledFiles->isNotEmpty()) {

            $filesToHandle = $files->filter(function ($file) use ($unHandledFiles) {
                return $unHandledFiles->contains($file->getFileName());
            });

            $filesToHandle->each(function ($file) {

                $this->info('Synchronising ' . $file->getFileName());

                $this->synchronizer->run($file);

                $this->info('Synchronized ' . $file->getFileName());
            });

            return $this->info('Synchronizations completed');
        }

        return $this->info('No synchronizations found.');
    }

}
