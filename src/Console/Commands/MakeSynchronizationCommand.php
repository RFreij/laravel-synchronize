<?php

namespace LaravelSynchronize\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use LaravelSynchronize\Console\Synchronizer\Synchronizer;

class MakeSynchronizationCommand extends GeneratorCommand
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
    protected $signature = 'make:synchronization {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new synchronization file';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Synchronization';

    /**
     * Create a new controller creator command instance.
     *
     * @param  \LaravelSynchronize\Console\Synchronizer\Synchronizer  $synchronizer
     *
     * @return void
     */
    public function __construct(Synchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;

        parent::__construct($synchronizer->getFileSystem());
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $name = $this->qualifyClass(Str::studly($this->getNameWithSuffix()));
        $path = $this->getPath($this->getNameWithSuffix());

        if ($this->alreadyExists($this->getNameWithSuffix())) {
            $this->error($this->type . ' already exists!');

            return false;
        }

        $this->files->put($path, $this->buildClass($name));
        $this->info($this->type . ' created successfully.');
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     *
     * @return bool
     */
    protected function alreadyExists($rawName): bool
    {
        return $this->synchronizer->hasSynchronization(Str::studly($rawName));
    }

    /**
     * Get the destination class name.
     *
     * @return string
     */
    protected function getNameWithSuffix(): string
    {
        return $this->getNameInput() . 'Synchronization';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function getPath($name): string
    {
        return $this->synchronizer->getDirectory() . '/' . $this->getDatePrefix() . '_' . Str::studly($name) . '.php';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/Synchronization.stub';
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }
}
