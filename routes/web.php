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
    Route::any('/dashboard', [App\Http\Controllers\MovieController::class, 'dashboard'])->name('dashboard');
    Route::get('/watchlist/check/{movie}', [App\Http\Controllers\MovieController::class, 'check'])->name('watchlist.check');
    Route::post('/watchlist/toggle/{movie}', [App\Http\Controllers\MovieController::class, 'toggleWatchlist'])->name('watchlist.toggle');
    Route::get('/watchlist', [App\Http\Controllers\MovieController::class, 'watchlist'])->name('watchlist.dashboard');
    Route::get('/watched', [App\Http\Controllers\MovieController::class, 'watched'])->name('watched.dashboard');
    Route::get('/watched/check/{movie}', [App\Http\Controllers\MovieController::class, 'checkWatched'])->name('watched.check');
    Route::post('/watched/toggle/{movie}', [App\Http\Controllers\MovieController::class, 'toggleWatched'])->name('watched.toggle');
    Route::post('/movies/scan', [App\Http\Controllers\MovieController::class, 'scanMovies'])->name('movies.scan');  
    Route::get('/movies/{id}/secure-url', [App\Http\Controllers\MovieController::class, 'getSecureVideoUrl'])->name('movies.secure-url');
    Route::get('/movies/stream/{token}', [App\Http\Controllers\MovieController::class, 'streamVideo'])->name('movies.stream');  

   
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/movies', [App\Http\Controllers\AdminController::class, 'movies'])->name('admin.movies');
    Route::post('/movies/scan', [App\Http\Controllers\MovieController::class, 'scanMovies'])->name('admin.movies.scan');
    Route::post('/movies/move', [App\Http\Controllers\MovieController::class, 'moveMovies'])->name('admin.movies.move');
    Route::delete('/movies/{id}', [App\Http\Controllers\AdminController::class, 'deleteMovie'])->name('admin.movies.delete');
    Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
    Route::put('/users/{id}', [App\Http\Controllers\AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{id}', [App\Http\Controllers\AdminController::class, 'deleteUser'])->name('admin.users.delete');
});

require __DIR__.'/auth.php';
