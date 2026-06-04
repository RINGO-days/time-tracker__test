<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AdminController;

Route::middleware('auth','verified')->group(function(){
    Route::get('/',[AttendanceController::class,'index']);
    Route::get('/list',[AttendanceController::class,'list']);
    Route::post('/attendance',[AttendanceController::class,'attendance']);
    Route::post('/rest', [AttendanceController::class, 'rest']);
    Route::get('/detail', [AttendanceDetailController::class, 'detail']);
    Route::post('/detail/propose/{id}', [AttendanceDetailController::class, 'propose']);
    Route::get('/detail/propose/{id}', [AttendanceDetailController::class, 'detailConfirmShow']);
    Route::get('/applyList', [AttendanceDetailController::class, 'applyList']);
    Route::get('/stamp_correction_request', [AttendanceDetailController::class, '']);

    Route::prefix('admin')->group(function(){
        Route::get('/dailyAttendance',[AdminController::class,'dailyAttendance']);
        Route::get('/staff/list',[AdminController::class,'staffList']);
    });
});






