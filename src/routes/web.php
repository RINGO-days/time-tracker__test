<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;

route::get('/index',[AttendanceController::class,'index']);
route::post('/attendance',[AttendanceController::class,'attendance']);
route::get('/show',[AdminController::class,'show']);




