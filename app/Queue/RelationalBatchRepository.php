<?php

namespace App\Queue;

use App\Database\RelationalFields;
use Closure;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Str;

class RelationalBatchRepository extends \Illuminate\Bus\DatabaseBatchRepository
{
    public function store(PendingBatch $batch)
    {
        if (!RelationalFields::available($this->table)) return parent::store($batch);
        return $this->connection->transaction(function () use ($batch) {
            $id = (string) Str::orderedUuid();
            $this->connection->table($this->table)->insert([
                'id'=>$id,'name'=>$batch->name,'total_jobs'=>0,'pending_jobs'=>0,'failed_jobs'=>0,
                'options'=>$this->serialize($batch->options),'created_at'=>time(),'cancelled_at'=>null,'finished_at'=>null,
            ]);
            RelationalFields::write($this->table,$id,'failed_job_ids',[]);
            return $this->find($id);
        });
    }

    protected function updateAtomicValues(string $batchId, Closure $callback)
    {
        if (!RelationalFields::available($this->table)) return parent::updateAtomicValues($batchId,$callback);
        return $this->connection->transaction(function () use ($batchId,$callback) {
            $batch = $this->connection->table($this->table)->where('id',$batchId)->lockForUpdate()->first();
            if (!$batch) return [];
            $batch->failed_job_ids = json_encode(RelationalFields::read($this->table,$batchId,'failed_job_ids') ?? [],JSON_THROW_ON_ERROR);
            $values = $callback($batch);
            $columns = $values;
            if (isset($columns['failed_job_ids'])) {
                RelationalFields::write($this->table,$batchId,'failed_job_ids',json_decode($columns['failed_job_ids'],false,512,JSON_THROW_ON_ERROR));
                unset($columns['failed_job_ids']);
            }
            $this->connection->table($this->table)->where('id',$batchId)->update($columns);
            return $values;
        });
    }

    protected function toBatch($batch)
    {
        if (RelationalFields::available($this->table)) $batch->failed_job_ids = json_encode(RelationalFields::read($this->table,$batch->id,'failed_job_ids') ?? [],JSON_THROW_ON_ERROR);
        return parent::toBatch($batch);
    }
}
