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
    Route::get('/stamp_correction_request/list', [AttendanceDetailController::class, 'applyList'])->middleware('admin');
    Route::get('/stamp_correction_request', [AttendanceDetailController::class, '']);
    Route::get('/attendance/report',[AttendanceController::class,'report']);

    Route::prefix('admin')->group(function(){
        Route::get('/dailyAttendance',[AdminController::class,'dailyAttendance']);
        Route::get('/staff/list',[AdminController::class,'staffList']);
        Route::get('/csvExport',[AdminController::class,'export']);
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class,'requestShow']);
        Route::post('/stamp_correction_request/approve/update/{attendance_correct_request_id}', [AdminController::class,'approve']);
    });
});






