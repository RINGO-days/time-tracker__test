<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AdminController;

Route::get('/admin/login',function(){
    return view('admin.adminLogin',['nav' => false]);
});

Route::middleware('auth','verified')->group(function(){
    Route::post('/detail/propose/{id}', [AttendanceDetailController::class, 'propose']);

    Route::middleware('staff')->group(function(){
        Route::get('/',[AttendanceController::class,'index']);
        Route::get('/list',[AttendanceController::class,'list']);
        Route::post('/attendance',[AttendanceController::class,'attendance']);
        Route::post('/rest', [AttendanceController::class, 'rest']);
        Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'detail']);
        Route::get('/detail/propose/{id}', [AttendanceDetailController::class, 'detailConfirmShow']);
        Route::get('/stamp_correction_request/list', [AttendanceDetailController::class, 'applyList'])->middleware('viewGuard');
        Route::get('/stamp_correction_request', [AttendanceDetailController::class, '']);
        Route::get('/attendance/report',[AttendanceController::class,'report']);
    });

    Route::middleware('admin')->group(function(){
        Route::prefix('admin')->group(function(){
            Route::get('/attendance/list',[AdminController::class,'dailyAttendance']);
            Route::get('/attendance/{id}',[AdminController::class,'editDetail']);
            Route::get('/staff/list',[AdminController::class,'staffList']);
            Route::get('/attendance/staff/{id}',[AdminController::class,'staffMonthlyAttendance']);
            Route::get('/csvExport/{id}',[AdminController::class,'export']);
            Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class,'requestShow']);
            Route::post('/stamp_correction_request/approve/update/{attendance_correct_request_id}', [AdminController::class,'approve']);
        });
    });
});






