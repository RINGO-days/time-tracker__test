<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;

route::get('/',[AttendanceController::class,'index']);
route::get('/list',[AttendanceController::class,'list']);
route::post('/attendance',[AttendanceController::class,'attendance']);
route::get('/show',[AdminController::class,'show']);
Route::post('/rest', [AttendanceController::class, 'rest']);
Route::get('/detail', [AttendanceController::class, 'detal']);





