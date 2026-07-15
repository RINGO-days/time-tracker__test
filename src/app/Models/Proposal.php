<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'target_date',
        'proposed_attendance',
        'proposed_rest',
        'remarks',
        'status',
    ];

    protected $casts = [
        'proposed_attendance' => 'array',
        'proposed_rest' => 'array',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function attendance() : BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
