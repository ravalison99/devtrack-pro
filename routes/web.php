<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => 'Tableau de bord (à construire)')->name('dashboard');

    Route::get('/stages', [StageController::class, 'index'])->name('stages.index');
    Route::get('/stages/create', [StageController::class, 'create'])->name('stages.create');
    Route::post('/stages', [StageController::class, 'store'])->name('stages.store');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/stages/{stage}/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
});
