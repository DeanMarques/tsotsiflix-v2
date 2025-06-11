<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\MovieController::class, 'dashboard'])->name('dashboard');
    Route::post('/movies/scan', [App\Http\Controllers\MovieController::class, 'scanMovies'])->name('movies.scan');  
    Route::get('/movies/{id}/secure-url', [App\Http\Controllers\MovieController::class, 'getSecureVideoUrl'])->name('movies.secure-url');
    Route::get('/movies/stream/{token}', [App\Http\Controllers\MovieController::class, 'streamVideo'])->name('movies.stream');  
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
