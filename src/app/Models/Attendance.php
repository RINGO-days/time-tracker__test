<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'attendance_date',
        'attendance_time',
        'leave_time',
        'comment'
    ];

    protected $casts = [
        'attendance_time' => 'datetime',
        'leave_time' => 'datetime',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rests() : HasMany
    {
        return $this->hasMany(Rest::class);
    }

    public function proposals() : HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}