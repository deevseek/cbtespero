<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudentExamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/server-time', fn () => response()->json([
        'server_time' => now()->toISOString(),
        'timestamp' => now()->timestamp,
    ]));

    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/student/exams', [StudentExamController::class, 'exams']);
        Route::post('/student/exams/{exam}/start', [StudentExamController::class, 'start'])->middleware('throttle:20,1');
        Route::get('/student/sessions/{result}/questions', [StudentExamController::class, 'questions']);
        Route::post('/student/sessions/{result}/answer', [StudentExamController::class, 'answer'])->middleware('throttle:120,1');
        Route::post('/student/sessions/{result}/flag', [StudentExamController::class, 'flag'])->middleware('throttle:120,1');
        Route::post('/student/sessions/{result}/cheating-log', [StudentExamController::class, 'cheatingLog'])->middleware('throttle:60,1');
        Route::post('/student/sessions/{result}/heartbeat', [StudentExamController::class, 'heartbeat'])->middleware('throttle:120,1');
        Route::post('/student/sessions/{result}/submit', [StudentExamController::class, 'submit'])->middleware('throttle:20,1');
        Route::get('/student/results', [StudentExamController::class, 'results']);
    });
});
