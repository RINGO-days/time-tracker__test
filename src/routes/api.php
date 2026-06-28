<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceApiController;

Route::prefix('v1')->group(function(){
    Route::apiResource('attendance-records',AttendanceApiController::class);
});