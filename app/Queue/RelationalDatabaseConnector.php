<?php

namespace App\Queue;

class RelationalDatabaseConnector extends \Illuminate\Queue\Connectors\DatabaseConnector
{
    public function connect(array $config)
    {
        return new RelationalDatabaseQueue($this->connections->connection($config['connection'] ?? null),$config['table'],$config['queue'],$config['retry_after'] ?? 60,$config['after_commit'] ?? null);
    }
}
