<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// only for people who are not signed in yet
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');

    // a handful of tries per minute is fine for a person, not for password guessing
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

// everything else needs a signed-in user
Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
