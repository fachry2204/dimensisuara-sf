<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User\User;

class Broadcast extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject',
        'message',
        'created_by',
        'excluded_user_ids',
        'recipient_count',
        'status',
        'sent_at',
        'selection_mode',
        'batch_id',
        'total_chunks',
        'completed_chunks',
        'failed_chunks',
    ];

    protected $casts = [
        'excluded_user_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_chunks' => 'integer',
        'completed_chunks' => 'integer',
        'failed_chunks' => 'integer',
    ];

    /**
     * Relationship: Broadcast created by admin user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor: Get count of excluded users
     */
    public function getExcludedCountAttribute(): int
    {
        return is_array($this->excluded_user_ids) ? count($this->excluded_user_ids) : 0;
    }

    /**
     * Get recipient user IDs for this broadcast (for chunking)
     */
    public function getRecipientIds(): array
    {
        $query = User::whereHas('profile');

        if (!empty($this->excluded_user_ids)) {
            if ($this->selection_mode === 'include') {
                // Include mode: only send to selected users
                $query->whereIn('id', $this->excluded_user_ids);
            } else {
                // Exclude mode: send to all except selected users
                $query->whereNotIn('id', $this->excluded_user_ids);
            }
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * Helper method to get recipients for this broadcast (for display)
     */
    public function getRecipients()
    {
        $query = User::whereHas('profile');

        if (!empty($this->excluded_user_ids)) {
            if ($this->selection_mode === 'include') {
                // Include mode: only send to selected users
                $query->whereIn('id', $this->excluded_user_ids);
            } else {
                // Exclude mode: send to all except selected users
                $query->whereNotIn('id', $this->excluded_user_ids);
            }
        }

        return $query->get();
    }

    /**
     * Scope: Get draft broadcasts
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Get sent broadcasts
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
}
