<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProofOfWorkController;
use Illuminate\Support\Facades\Route;

// open to everyone: guests get the landing page, signed-in users their own home page
Route::get('/', HomeController::class)->name('home');

// only for people who are not signed in yet
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');

    // a handful of tries per minute is fine for a person, not for password guessing
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

// everything else needs a signed-in user
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // every role has a profile; it always works on the signed-in user, so there is no id to tamper with
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:6,1')->name('profile.password');

    // booking time is for consultants only, checked on the server by the role middleware
    Route::middleware('role:consultant')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    });

    // budget per order is for the client side only, the consultant gets a 403
    Route::middleware('role:project_lead,purchasing,finance')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    });

    // follow-up list: purchasing and project lead (role matrix), not finance
    Route::middleware('role:project_lead,purchasing')->group(function () {
        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
        Route::get('/follow-ups/{order}/proposal', [FollowUpController::class, 'proposal'])->name('follow-ups.proposal');
    });

    // proof of work is for finance only: approve or reject the monthly hours
    Route::middleware('role:finance')->group(function () {
        Route::get('/proof-of-work', [ProofOfWorkController::class, 'index'])->name('proof-of-work.index');
        Route::post('/proof-of-work', [ProofOfWorkController::class, 'decide'])->name('proof-of-work.decide');
    });

    // the administrator sees all accounts and adds new ones, nobody else gets in
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users/create', [AdminController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminController::class, 'store'])->name('users.store');
    });

    // JSON endpoints, documented in public/openapi.yaml. They use the same sign-in session as the
    // pages and the same role checks, so the API can never show more than the pages do
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/orders', [OrderApiController::class, 'index'])
            ->middleware('role:project_lead,purchasing,finance')->name('orders');
        Route::get('/follow-ups', [OrderApiController::class, 'followUps'])
            ->middleware('role:project_lead,purchasing')->name('follow-ups');
    });
});
