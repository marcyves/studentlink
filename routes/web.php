<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Professor\RubricController as ProfessorRubricController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\PeerEvaluationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::post('/courses/join', [StudentDashboardController::class, 'joinCourse'])->name('courses.join');
    Route::post('/groups', [StudentDashboardController::class, 'storeGroup'])->name('groups.store');
    Route::post('/groups/join', [StudentDashboardController::class, 'joinGroup'])->name('groups.join');
    Route::get('/evaluations', [PeerEvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/{evaluation}', [PeerEvaluationController::class, 'show'])->name('evaluations.show');
    Route::put('/evaluations/{evaluation}', [PeerEvaluationController::class, 'update'])->name('evaluations.update');
});

Route::middleware(['auth', 'verified', 'professor'])->prefix('professor')->name('professor.')->group(function () {
    Route::get('/projects/{project}/rubric', [ProfessorRubricController::class, 'edit'])->name('rubrics.edit');
    Route::put('/projects/{project}/rubric', [ProfessorRubricController::class, 'update'])->name('rubrics.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
