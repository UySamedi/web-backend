<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;

// Public routes - no auth required
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Courses CRUD routes for admins
    Route::apiResource('courses', CourseController::class);

    // Enrollment routes grouped under /enrollments prefix
    Route::prefix('enrollments')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index']);                    // Admin: get all enrollments
        Route::post('/', [EnrollmentController::class, 'enroll']);                  // Student: enroll in a course
        Route::post('/{enrollment}/approve', [EnrollmentController::class, 'approve']); // Admin: approve enrollment
        Route::post('/{enrollment}/reject', [EnrollmentController::class, 'reject']);   // Admin: reject enrollment
    });

    // Student: view own enrollments
    Route::get('/my-enrollments', [EnrollmentController::class, 'myEnrollments']);
});
