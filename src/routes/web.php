<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CsvExportController;
use App\Http\Controllers\AttendanceReportController;

Route::get('/admin/login',function(){
    return view('admin.adminLogin');
});

Route::middleware('auth','verified')->group(function(){
    Route::post('/detail/propose/{id}', [AttendanceDetailController::class, 'propose']);
    Route::post('/newDetail/propose/staff/{id}', [AttendanceDetailController::class, 'newDetailPropose']);
    Route::get('/stamp_correction_request/list', [AttendanceDetailController::class, 'applyList'])->middleware('viewGuard');

    Route::middleware('staff')->group(function(){
        Route::get('/',[AttendanceController::class,'index']);
        Route::post('/attendance',[AttendanceController::class,'attendance']);
        Route::post('/rest', [AttendanceController::class, 'rest']);
        Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'detail']);
        Route::get('/attendance/newDetail', [AttendanceDetailController::class, 'newDetail']);
        Route::get('/detail/propose/{id}', [AttendanceDetailController::class, 'detailConfirmShow']);
        Route::get('/attendance/report',[AttendanceReportController::class,'report']);
    });

    Route::middleware('admin')->group(function(){
        Route::prefix('admin')->group(function(){
            Route::get('/attendance/list',[AdminController::class,'dailyAttendance']);
            Route::get('/attendance/{id}',[AdminController::class,'editDetail']);
            Route::get('/staff/list',[AdminController::class,'staffList']);
            Route::get('/attendance/newDetail', [AdminController::class, 'newDetailByAdmin']);
            Route::get('/attendance/staff/{id}',[AdminController::class,'staffMonthlyAttendance']);
            Route::get('/csvExport/{id}',[CsvExportController::class,'export']);
            Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class,'requestShow']);
            Route::post('/stamp_correction_request/approve/update/{attendance_correct_request_id}', [AdminController::class,'approve']);
        });
    });
});






