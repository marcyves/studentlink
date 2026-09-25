<?php

use App\Http\Controllers\Admin\AccessRequestController as AdminAccessRequestController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Professor\CourseController as ProfessorCourseController;
use App\Http\Controllers\Professor\GradeExportController;
use App\Http\Controllers\Professor\ProjectController as ProfessorProjectController;
use App\Http\Controllers\Professor\RubricController as ProfessorRubricController;
use App\Http\Controllers\ProfessorAccessRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\GroupChatController;
use App\Http\Controllers\Student\PeerEvaluationController;
use App\Http\Controllers\Student\SubmissionController as StudentSubmissionController;
use App\Services\EmailDomainService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'registrationHint' => app(EmailDomainService::class)->registrationHint(),
        ]);
});

Route::post('/professor-access', [ProfessorAccessRequestController::class, 'store'])
    ->name('professor-access.store');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::post('/courses/join', [StudentDashboardController::class, 'joinCourse'])->name('courses.join');
    Route::post('/groups', [StudentDashboardController::class, 'storeGroup'])->name('groups.store');
    Route::post('/groups/join', [StudentDashboardController::class, 'joinGroup'])->name('groups.join');
    Route::post('/groups/{group}/submission', [StudentSubmissionController::class, 'store'])
        ->name('groups.submission.store');
    Route::get('/evaluations', [PeerEvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/{evaluation}', [PeerEvaluationController::class, 'show'])->name('evaluations.show');
    Route::put('/evaluations/{evaluation}', [PeerEvaluationController::class, 'update'])->name('evaluations.update');
    Route::get('/chat', [GroupChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{group}', [GroupChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{group}', [GroupChatController::class, 'store'])->name('chat.store');
});

Route::middleware(['auth', 'verified', 'professor'])->prefix('professor')->name('professor.')->group(function () {
    Route::post('/courses', [ProfessorCourseController::class, 'store'])->name('courses.store');
    Route::put('/courses/{course}', [ProfessorCourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [ProfessorCourseController::class, 'destroy'])->name('courses.destroy');
    Route::put('/courses/{course}/domains', [ProfessorCourseController::class, 'updateDomains'])
        ->name('courses.domains.update');
    Route::post('/courses/{course}/projects', [ProfessorProjectController::class, 'store'])
        ->name('courses.projects.store');
    Route::put('/projects/{project}', [ProfessorProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProfessorProjectController::class, 'destroy'])->name('projects.destroy');
    Route::put('/projects/{project}/deliverable', [ProfessorProjectController::class, 'updateDeliverable'])
        ->name('projects.deliverable.update');
    Route::get('/projects/{project}/rubric', [ProfessorRubricController::class, 'edit'])->name('rubrics.edit');
    Route::put('/projects/{project}/rubric', [ProfessorRubricController::class, 'update'])->name('rubrics.update');
    Route::get('/projects/{project}/export', GradeExportController::class)->name('grades.export');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/access-requests/{accessRequest}/accept', [AdminAccessRequestController::class, 'accept'])
        ->name('access-requests.accept');
    Route::post('/access-requests/{accessRequest}/reject', [AdminAccessRequestController::class, 'reject'])
        ->name('access-requests.reject');
    Route::get('/access-requests', fn () => redirect()->route('admin.dashboard'));
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
