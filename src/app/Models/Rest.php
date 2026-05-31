<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'rest_start',
        'rest_end',
    ];

    protected $casts = [
        'rest_start' => 'datetime',
        'rest_end' => 'datetime',
    ];
}
