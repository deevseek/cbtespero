<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Student\ExamSessionController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ResultController as StudentResultController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');
Route::redirect('/login', '/admin/login')->name('login');

Route::get('/student/login', [AuthController::class, 'showLogin'])->name('student.login');
Route::post('/student/login', [AuthController::class, 'login'])->name('student.login.attempt');
Route::post('/student/logout', [AuthController::class, 'logout'])->name('student.logout');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('legacy-admin')->name('admin.')->group(function () {
    Route::redirect('/', '/legacy-admin/dashboard')->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('students', StudentController::class)->except(['create', 'show', 'edit']);
    Route::resource('questions', QuestionController::class)->except(['create', 'show', 'edit']);
    Route::resource('exams', ExamController::class)->except(['create', 'show', 'edit']);
    Route::post('exams/{exam}/regenerate-token', [ExamController::class, 'regenerateToken'])->name('exams.regenerate-token');

    Route::get('/monitoring', [ModuleController::class, 'monitoring'])->name('monitoring');
    Route::get('/attendance', [ModuleController::class, 'attendance'])->name('attendance');
    Route::get('/cards', [ModuleController::class, 'cards'])->name('cards');
    Route::get('/recap', [ModuleController::class, 'recap'])->name('recap');
    Route::get('/config', [ModuleController::class, 'config'])->name('config');
    Route::post('/config', [ModuleController::class, 'saveConfig'])->name('config.save');
});

Route::middleware('student.auth')->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
    Route::get('/exams', [StudentExamController::class, 'index'])->name('exams');
    Route::post('/exams/{exam}/token', [StudentExamController::class, 'token'])->name('exams.token');
    Route::get('/exams/{exam}/start', [StudentExamController::class, 'start'])->name('exams.start');
    Route::post('/exams/{exam}/begin', [StudentExamController::class, 'begin'])->name('exams.begin');
    Route::get('/results', StudentResultController::class)->name('results');
    Route::get('/profile', StudentProfileController::class)->name('profile');
    Route::get('/room/{result}', [ExamSessionController::class, 'room'])->name('exams.room');
    Route::post('/exams/{exam}/violations', [ExamSessionController::class, 'violation'])->name('exams.violations');
    Route::post('/exams/{exam}/heartbeat', [ExamSessionController::class, 'heartbeat'])->name('exams.heartbeat');
    Route::post('/room/{result}/answer', [ExamSessionController::class, 'answer'])->name('exams.answer');
    Route::post('/room/{result}/cheating-log', [ExamSessionController::class, 'logCheating'])->name('exams.cheating-log');
    Route::post('/room/{result}/submit', [ExamSessionController::class, 'submit'])->name('exams.submit');
});
