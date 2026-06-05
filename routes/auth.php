<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Minimal auth routes used by the portal. These assume Auth controllers
| exist in App\Http\Controllers\Auth\ (LoginController, RegisterController,
| ForgotPasswordController, ResetPasswordController).
|
*/

// Login
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);

// Logout
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Password Reset
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Google Auth
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('login.google.callback');
