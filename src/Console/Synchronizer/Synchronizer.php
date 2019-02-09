<?php

namespace LaravelSynchronize\Console\Synchronizer;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class Synchronizer
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \LaravelSynchronize\Console\Synchronizer\SynchronizationRepository  $repository
     * @return void
     * @author Roy Freij <info@royfreij.nl>
     */
    public function __construct(FileSystem $files, SynchronizationRepository $repository)
    {
        $this->repository = $repository;
        $this->files = $files;
    }

    /**
     * Get the directory where synchronizations are saved
     * It will be created if the directive doesn't exist.
     *
     * @return string
     * @author Roy Freij <info@royfreij.nl>
     */
    public function getDirectory()
    {
        $directory = config('synchronizer.folder') ?? $this->getDefaultDirectory();
        $this->files->isDirectory($directory) ?: $this->files->makeDirectory($directory);

        return $directory;
    }

    /**
     * Get the name of the synchronization stripped of the date and time.
     *
     * @param  string  $path
     * @return string
     * @author Roy Freij <info@royfreij.nl>
     */
    public function getSynchronizationName($path)
    {
        return str_replace('.php', '', implode('_', array_slice(explode('_', $path), 4)));
    }

    /**
     * Get the class name of a synchronization name.
     *
     * @param  string  $name
     * @return string
     */
    public function getClassName(string $name)
    {
        return Str::studly($name);
    }

    /**
     * Determine if a synchronization exists with given name
     *
     * @param string $name
     * @return boolean
     * @author Roy Freij <info@royfreij.nl>
     */
    public function hasSynchronization(string $name)
    {
        return !empty($this->files->glob($this->synchronizer->getDirectory() . "/*_*_{$rawName}.php"));
    }

    /**
     * Resolve a synchronization instance from a file.
     *
     * @param  string  $file
     * @return object
     * @author Roy Freij <info@royfreij.nl>
     */
    public function resolve($file)
    {
        $class = $this->getClassName($file);

        return new $class;
    }

    /**
     * include the file with Require and call the class it's handler
     *
     * @param  \Symfony\Component\Finder\SplFileInfo $file
     * @return void
     * @author Roy Freij <info@royfreij.nl>
     */
    public function run(SplFileInfo $file)
    {
        $this->files->getRequire($file);

        $synchronization = $this->resolve(
            $file = $this->getSynchronizationName($file)
        );

        $this->databaseTransaction($synchronization);

        $this->tryHandle($synchronization);

        $this->databaseTransaction($synchronization, false);
    }

    /**
     * Try to execute the resolved class, if anything fails
     * rollback the database changes and throw an exception.
     * Rollback will only work when $withTransactions is true
     *
     * @param class $synchronization
     * @return void
     * @throws Exception
     * @author Roy Freij <info@royfreij.nl>
     */
    public function tryHandle($synchronization)
    {
        try {
            $synchronization->handle();

        } catch (\Exception $exception) {

            DB::rollBack();

            throw $exception;
        }
    }

    /**
     * If class has database transactions enabled, start the transaction
     * When the handler has been run without errors, commit the changes.
     *
     * @param class $synchronization
     * @param boolean $start
     * @return void
     * @author Roy Freij <info@royfreij.nl>
     */
    private function databaseTransaction($synchronization, $start = true)
    {
        if ($synchronization->withTransactions) {
            $start ? DB::beginTransaction() : DB::commit();
        }
    }

    /**
     * Get default directive synchronizations are stored
     *
     * @return string
     * @author Roy Freij <info@royfreij.nl>
     */
    private function getDefaultDirectory()
    {
        return database_path('synchronizations');
    }
}
