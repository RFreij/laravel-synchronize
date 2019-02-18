<?php

namespace LaravelSynchronize\Console\Synchronizer;

use Illuminate\Database\ConnectionResolver as Resolver;
use Illuminate\Support\Facades\DB;

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
        DB::table($this->getTable())->insert($record);
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
        return DB::table($this->getTable())->max('batch');
    }

    /**
     * Get a query builder for the synchronization table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return DB::table($this->getTable());
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
