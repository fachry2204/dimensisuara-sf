<?php

namespace App\Queue;

use App\Database\RelationalFields;
use Illuminate\Queue\DatabaseQueue;

class RelationalDatabaseQueue extends DatabaseQueue
{
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        if (!RelationalFields::available($this->table)) return parent::pushToDatabase($queue,$payload,$delay,$attempts);
        return RelationalFields::insert($this->table, $this->buildDatabaseRecord($this->getQueue($queue),$payload,$this->availableAt($delay),$attempts));
    }

    public function bulk($jobs, $data = '', $queue = null)
    {
        if (!RelationalFields::available($this->table)) return parent::bulk($jobs,$data,$queue);
        return $this->database->transaction(function () use ($jobs,$data,$queue) {
            foreach ((array) $jobs as $job) $this->pushToDatabase($queue,$this->createPayload($job,$this->getQueue($queue),$data),$job->delay ?? 0);
            return true;
        });
    }

    protected function getNextAvailableJob($queue)
    {
        if (!RelationalFields::available($this->table)) return parent::getNextAvailableJob($queue);
        $job = $this->database->table($this->table)->lock($this->getLockForPopping())
            ->where('queue',$this->getQueue($queue))
            ->where(function ($query) { $this->isAvailable($query); $this->isReservedButExpired($query); })
            ->orderBy('id','asc')->first();
        return $job ? new \Illuminate\Queue\Jobs\DatabaseJobRecord(RelationalFields::hydratePayload($this->table,$job)) : null;
    }
}
