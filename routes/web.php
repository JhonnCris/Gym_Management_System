<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\PaymentManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Member\MemberPageController;
use App\Http\Controllers\Staff\StaffPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.forgot');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminPageController::class, 'users'])->name('users');
    Route::get('/payments', [AdminPageController::class, 'payments'])->name('payments');
    Route::get('/classes', [AdminPageController::class, 'classes'])->name('classes');
    Route::get('/attendance', [AdminPageController::class, 'attendance'])->name('attendance');
    Route::get('/equipment', [AdminPageController::class, 'equipment'])->name('equipment');
    Route::get('/reports', [AdminPageController::class, 'reports'])->name('reports');
    Route::get('/notifications/data', [AdminPageController::class, 'notifications'])->name('notifications.data');


    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/data', [UserManagementController::class, 'index'])->name('data');
        Route::get('/suggestions', [UserManagementController::class, 'suggestions'])->name('suggestions');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::patch('/{user}/status', [UserManagementController::class, 'updateStatus'])->name('status');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('payments')->name('payments.')->group(function (): void {
        Route::get('/data', [PaymentManagementController::class, 'index'])->name('data');
        Route::get('/{payment}/proof', [PaymentManagementController::class, 'proof'])->name('proof');
        Route::patch('/{payment}/approve', [PaymentManagementController::class, 'approve'])->name('approve');
        Route::patch('/{payment}/reject', [PaymentManagementController::class, 'reject'])->name('reject');
    });
});

Route::middleware(['auth', 'staff'])->prefix('staff')->name('staff.')->group(function (): void {
    Route::get('/dashboard', [StaffPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/check-in', [StaffPageController::class, 'checkin'])->name('checkin');
    Route::post('/check-in/submit', [StaffPageController::class, 'storeCheckin'])->name('checkin.store');
    Route::post('/check-in/quick-checkout', [StaffPageController::class, 'quickCheckout'])->name('checkin.quick-checkout');
    Route::get('/classes', [StaffPageController::class, 'classes'])->name('classes');
    Route::post('/classes', [StaffPageController::class, 'storeClass'])->name('classes.store');
    Route::post('/classes/{gymClass:class_id}/assign-trainer', [StaffPageController::class, 'assignTrainer'])->name('classes.assign-trainer');
    Route::get('/members', [StaffPageController::class, 'members'])->name('members');
    Route::get('/equipment', [StaffPageController::class, 'equipment'])->name('equipment');
    Route::post('/equipment', [StaffPageController::class, 'storeEquipment'])->name('equipment.store');


});

Route::middleware(['auth', 'member'])->prefix('member')->name('member.')->group(function (): void {
    Route::get('/dashboard', [MemberPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [MemberPageController::class, 'profile'])->name('profile');
    Route::get('/classes', [MemberPageController::class, 'classes'])->name('classes');
    Route::get('/payments', [MemberPageController::class, 'payments'])->name('payments');
    Route::post('/payments/subscribe', [MemberPageController::class, 'subscribe'])->name('payments.subscribe');
});
