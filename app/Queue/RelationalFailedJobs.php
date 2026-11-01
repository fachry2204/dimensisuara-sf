<?php

namespace App\Queue;

use App\Database\RelationalFields;
use Illuminate\Support\Facades\Date;

class RelationalFailedJobs extends \Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider
{
    public function log($connection, $queue, $payload, $exception)
    {
        if (!RelationalFields::available($this->table)) return parent::log($connection,$queue,$payload,$exception);
        $uuid = json_decode($payload,true,512,JSON_THROW_ON_ERROR)['uuid'];
        RelationalFields::insert($this->table,[
            'uuid'=>$uuid,'connection'=>$connection,'queue'=>$queue,'payload'=>$payload,
            'exception'=>(string) mb_convert_encoding($exception,'UTF-8'),'failed_at'=>Date::now(),
        ]);
        return $uuid;
    }

    public function all()
    {
        if (!RelationalFields::available($this->table)) return parent::all();
        return $this->getTable()->orderBy('id','desc')->get()->map(function ($record) {
            RelationalFields::hydratePayload($this->table,$record);
            $record->id = $record->uuid; unset($record->uuid);
            return $record;
        })->all();
    }

    public function find($id)
    {
        if (!RelationalFields::available($this->table)) return parent::find($id);
        $record = $this->getTable()->where('uuid',$id)->first();
        if ($record) {
            RelationalFields::hydratePayload($this->table,$record);
            $record->id = $record->uuid; unset($record->uuid);
        }
        return $record;
    }
}
