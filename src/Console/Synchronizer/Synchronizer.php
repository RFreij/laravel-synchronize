<?php

namespace LaravelSynchronize\Console\Synchronizer;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * The Repository instance.
     *
     * @var \LaravelSynchronize\Console\Synchronizer\SynchronizerRepository $repository
     */
    protected $repository;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \LaravelSynchronize\Console\Synchronizer\SynchronizerRepository  $repository
     * @return void
     * @author Roy Freij <info@royfreij.nl>
     */
    public function __construct(FileSystem $files, SynchronizerRepository $repository)
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
     * Get the instance of Filesystem
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFileSystem()
    {
        return $this->files;
    }

    /**
     * Get all synchronization files
     *
     * @return \Illuminate\Support\Collection
     * @author Roy Freij <info@royfreij.nl>
     */
    public function getSynchronizations(): Collection
    {
        return collect($this->files->files($this->getDirectory()))
            ->filter()
            ->values()
            ->keyBy(function ($file) {
                return $this->getSynchronizationName($file);
            })
            ->sortBy(function ($file, $key) {
                return $key;
            });
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
        $path = str_replace($this->getDirectory(), '', $path);

        return str_replace('.php', '', basename($path));
    }

    /**
     * Get the class name of a synchronization name.
     *
     * @param  string  $name
     * @return string
     *
     * @deprecated not used anymore
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
        return !empty($this->files->glob($this->getDirectory() . "/*_*_{$name}.php"));
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
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class();
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
            $this->getSynchronizationName($file)
        );

        $this->databaseTransaction($synchronization);

        $this->tryHandle($synchronization);

        $this->repository->log(
            $file->getFileName(),
            $this->repository->getNextBatchNumber()
        );

        $this->databaseTransaction($synchronization, false);
    }

    /**
     * Try to execute the resolved class, if anything fails
     * rollback the database changes and throw an exception.
     * Rollback will only work when $withTransactions is true
     *
     * @param mixed $synchronization
     * @return void
     * @throws \Exception
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
     * If the synchronization has database transactions enabled, start the transaction
     * When the handler has been run without errors, commit the changes.
     *
     * @param mixed $synchronization
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
