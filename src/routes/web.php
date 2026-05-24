<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

route::get('/attendance',[AttendanceController::class,'Attendance']);


