<?php

namespace LaravelSynchronize\Console\Synchronizer;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
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
     *
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
    public function getRan(): array
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
    public function getLast(): array
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('synchronization', 'desc')->get()->all();
    }

    /**
     * Log that a synchronization was run.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  string  $operation
     *
     * @return void
     */
    public function log(string $file, int $batch, string $operation): void
    {
        $record = ['synchronization' => $file];

        DB::table($this->getTable())
            ->when($operation === 'up', function ($query) use ($record, $batch) {
                $query->updateOrInsert($record, [
                    'batch' => $batch,
                ]);
            }, function ($query) use ($file) {
                $query->where('synchronization', $file)->delete();
            });
    }

    /**
     * Get the next synchronization batch number.
     *
     * @return int
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last synchronization batch number.
     *
     * @return int
     */
    public function getLastBatchNumber(): int
    {
        return DB::table($this->getTable())->max('batch') ?? 0;
    }

    /**
     * Get a query builder for the synchronization table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table(): Builder
    {
        return DB::table($this->getTable());
    }

    /**
     * Retrieve the table where synchronizations are being stored
     *
     * @return string
     */
    private function getTable(): string
    {
        return config('synchronizer.table') ?? 'synchronizations';
    }
}
