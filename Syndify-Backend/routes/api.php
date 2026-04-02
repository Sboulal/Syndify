<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// L'inscription
Route::post('/register', [AuthController::class, 'register']);

// Demander l'OTP l'connexion (Login)
Route::post('/login', [AuthController::class, 'requestLoginOtp']);

// Vérifier l'OTP (Khdama l'login w l'inscription)
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);