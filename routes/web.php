<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
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

    // booking time is for consultants only, checked on the server by the role middleware
    Route::middleware('role:consultant')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    });
});
