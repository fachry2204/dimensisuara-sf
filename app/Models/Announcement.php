<?php

namespace App\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'start_date',
        'end_date',
        'user_id',
    ];

    protected $casts = [
        'content' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the user who created this announcement
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User\Admin::class, 'user_id');
    }
}
