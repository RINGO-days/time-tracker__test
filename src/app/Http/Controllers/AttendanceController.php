<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function Attendance()
    {
        return view('staff.attendance');
    }
}
