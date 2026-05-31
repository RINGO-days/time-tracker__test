<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'attendance_date',
        'attendance_time',
        'leave_time',
    ];

    protected $casts = [
        'attendance_time' => 'datetime',
        'leave_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}