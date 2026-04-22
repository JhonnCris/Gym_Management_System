<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\GymClassController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/dashboard', DashboardController::class);

    Route::apiResource('users', UserController::class);
    Route::apiResource('members', MemberController::class);
    Route::apiResource('staff', StaffController::class);
    Route::apiResource('equipments', EquipmentController::class);
    Route::apiResource('classes', GymClassController::class);
    Route::apiResource('bookings', BookingController::class);
    Route::apiResource('attendances', AttendanceController::class);
});
