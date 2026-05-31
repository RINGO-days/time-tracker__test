<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'proposed_attendance',
        'proposed_rest',
        'remarks',
        'status',
    ];

    protected $casts = [
        'proposed_attendance' => 'array',
        'proposed_rest' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
