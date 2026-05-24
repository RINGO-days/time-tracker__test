<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;

route::get('/attendance',[AttendanceController::class,'Attendance']);
route::get('/show',[AdminController::class,'show']);




