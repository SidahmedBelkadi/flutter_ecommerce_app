<?php

use App\Http\Controllers\Auth\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

// Authentication Routes
Route::post("/login", [AuthenticationController::class, 'login']);
Route::post("/register", [AuthenticationController::class, 'register']);
Route::post("/register-verification", [AuthenticationController::class, 'verifyEmail']);
Route::post("/forgot-password", [AuthenticationController::class, 'forgotPassword']);
Route::post("/reset-password", [AuthenticationController::class, 'resetPassword']);
Route::post("/logout", [AuthenticationController::class, 'logout'])->middleware('auth:sanctum');

