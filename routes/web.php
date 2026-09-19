<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/stages', [StageController::class, 'index'])->name('stages.index');
    Route::get('/stages/create', [StageController::class, 'create'])->name('stages.create');
    Route::post('/stages', [StageController::class, 'store'])->name('stages.store');
    Route::patch('/stages/{stage}/status', [StageController::class, 'updateStatus'])->name('stages.updateStatus');
    Route::delete('/stages/{stage}', [StageController::class, 'destroy'])->name('stages.destroy');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create.mentor');
    Route::get('/stages/{stage}/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::post('/tasks/{task}/attachments', [TaskController::class, 'storeAttachment'])->name('tasks.attachments.store');

    Route::get('/projects/{project}/kanban', [KanbanController::class, 'show'])->name('kanban.show');

    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');

    Route::get('/reports', [WeeklyReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [WeeklyReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{id}/download', [WeeklyReportController::class, 'download'])->name('reports.download');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{documentId}/versions/{versionId}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('notifications.index');
    Route::delete('/notifications/{notification}', [DashboardController::class, 'deleteNotification'])->name('notifications.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
