<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AuthController;

Route::prefix('v1')->group(function(){
    Route::apiResource('attendance-records',AttendanceApiController::class,[
        'parameters' => [
            'attendance-records' => 'attendanceRecord',
        ]
    ])->only(['index','show']);

    Route::middleware('auth:sanctum')->group(function(){
        Route::apiResource('attendance-records',AttendanceApiController::class,[
            'parameters' => [
                'attendance-records' => 'attendanceRecord',
            ]
        ])->only(['store','update','destroy']);
    });

    Route::post('/login',[AuthController::class,'ApiLogin']);
});