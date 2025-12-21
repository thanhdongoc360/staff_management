<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\EmployeeDashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SalaryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes - All authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Debug endpoint
    Route::get('/debug', [DebugController::class, 'check']);
    
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    
    // Notifications routes (for all authenticated users)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/recent', [NotificationController::class, 'recent']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });
    
    // Schedules routes
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/today', [ScheduleController::class, 'today']);
        Route::get('/upcoming', [ScheduleController::class, 'upcoming']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        // Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [ScheduleController::class, 'store']);
            Route::put('/{id}', [ScheduleController::class, 'update']);
            Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        });
    });
    
    // Admin Dashboard routes
    Route::middleware('role:admin')->prefix('admin/dashboard')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/recent-employees', [AdminDashboardController::class, 'recentEmployees']);
        Route::get('/pending-leaves', [AdminDashboardController::class, 'pendingLeaves']);
        Route::get('/employee-stats', [AdminDashboardController::class, 'employeeStats']);
    });

    // Employees CRUD (admin only)
    Route::middleware('role:admin')->prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    });

    // Salaries
    Route::middleware('role:admin')->prefix('salaries')->group(function () {
        Route::get('/', [SalaryController::class, 'index']);
        Route::post('/', [SalaryController::class, 'store']);
        Route::put('/{id}', [SalaryController::class, 'update']);
        Route::delete('/{id}', [SalaryController::class, 'destroy']);
    });
    Route::middleware('role:employee')->get('/my-salaries', [SalaryController::class, 'mySalaries']);

    // Leave requests
    Route::middleware('role:admin')->prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/{id}/status', [LeaveRequestController::class, 'updateStatus']);
        Route::delete('/{id}', [LeaveRequestController::class, 'destroy']);
    });

    Route::middleware('role:employee')->group(function () {
        Route::get('/my-leaves', [LeaveRequestController::class, 'myLeaves']);
        Route::post('/my-leaves', [LeaveRequestController::class, 'store']);
    });
    
    // Employee Dashboard routes
    Route::middleware('role:employee')->prefix('employee/dashboard')->group(function () {
        Route::get('/stats', [EmployeeDashboardController::class, 'stats']);
        Route::get('/my-leaves', [EmployeeDashboardController::class, 'myLeaves']);
        Route::get('/leave-stats', [EmployeeDashboardController::class, 'leaveStats']);
    });
});
