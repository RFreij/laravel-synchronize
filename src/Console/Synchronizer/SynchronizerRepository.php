<?php

namespace LaravelSynchronize\Console\Synchronizer;

use Illuminate\Database\ConnectionResolver as Resolver;

class SynchronizerRepository
{

    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolver
     */
    protected $resolver;
    /**
     * The name of the synchronizations table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database synchronizations repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolver  $resolver
     * @param  string  $table
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        $this->table = $this->getTable();
        $this->resolver = $resolver;
    }

    /**
     * Get the ran synchronizations.
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('synchronization', 'asc')
            ->pluck('synchronization')->all();
    }

    /**
     * Create the synchronization repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();
        $schema->create($this->table, function ($table) {
            $table->increments('id');
            $table->string('synchronization');
            $table->integer('batch');
        });
    }

    /**
     * Get the last synchronization batch.
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());
        return $query->orderBy('synchronization', 'desc')->get()->all();
    }

    /**
     * Log that a synchronization was run.
     *
     * @param  string  $file
     * @param  int     $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['synchronization' => $file, 'batch' => $batch];
        $this->table()->insert($record);
    }

    /**
     * Get the next synchronization batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last synchronization batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->max('batch');
    }

    /**
     * Determine if the synchronization repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the synchronization table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }
    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Retrieve the table where synchronizations are being stored
     *
     * @return string
     * @author Roy Freij <info@royfreij.nl>
     */
    private function getTable()
    {
        return config('synchronizer.table') ?? 'synchronizations';
    }

}
