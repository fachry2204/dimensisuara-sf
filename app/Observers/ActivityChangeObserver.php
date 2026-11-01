<?php

namespace App\Observers;

use App\Services\ActivityLogDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ActivityChangeObserver
{
    public function updated(Model $model): void
    {
        if (!Schema::hasTable('logs')) return;
        $details = app(ActivityLogDetails::class);
        $changes = $details->changes($model);
        if (!$changes) return;
        $subject = $details->snapshot($model);
        $details->remember($model,$subject,$changes);
        Log::info($subject['type'].' diperbarui: '.$subject['name'],[
            'model_id'=>$model->getKey(),'model_type'=>get_class($model),
            'audit_subject'=>$subject,'audit_changes'=>$changes,
        ]);
    }
}
