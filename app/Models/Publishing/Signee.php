<?php

namespace App\Models\Publishing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;


use App\Models\Contract;
use App\Models\User\Profile;
use App\Models\Publishing\Publishing;
use App\Models\Publishing\Songwriter;

class Signee extends Model
{
    protected $table = 'publishing_signees';

    protected $fillable = [
        'token',
        'profileid',
        'full_name',
        'email',
        'ktp_num',
        'phone',
        'publisher',
        'lmk',
        'role',
        'share',
        'status',
        'accept_terms',
        'accepted_at',
    ];

    protected static function booted()
    {
        static::deleting(function ($signee) {
            // Prevent direct deletion of signees
            // Signees should only be deleted via publishing cascade or songwriter cascade
            // Check if we're in a force delete context or database cascade
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            $allowedCallers = ['forceDelete', 'cascadeDelete'];

            foreach ($trace as $frame) {
                if (isset($frame['function']) && in_array($frame['function'], $allowedCallers)) {
                    return; // Allow deletion from cascade
                }
            }

            // If called directly, prevent deletion
            if (!app()->runningInConsole()) {
                throw new \Exception(
                    "Signee records cannot be deleted directly. " .
                    "They are managed through publishing and songwriter lifecycle."
                );
            }
        });
    }

    public function publishing(): BelongsTo
    {
        return $this->belongsTo(Publishing::class, 'publishingid');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function contract()
    {
        return $this->morphOne(Contract::class, 'owner');
    }

    public function signature()
    {
        return $this->morphOne(\App\Models\File::class, 'owner')->where('type', 'signature');
    }

    public function sendNotification(BaseNotification $notification)
    {
        $user = (new AnonymousNotifiable)->route('mail', $this->email);

        // Send notification
        Notification::send($user, $notification);
    }

    public function getRoleNameAttribute(): string
    {
        return \App\Enums\SongwriterRole::tryFrom($this->role)?->label() ?? $this->role;
    }
}
