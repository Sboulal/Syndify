<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LotController;
use App\Http\Controllers\CoproprietaireController;
use App\Http\Controllers\CoproprieteController;
use App\Http\Controllers\CleRepartitionController;

// L'inscription
Route::post('/register', [AuthController::class, 'register']);

// Demander l'OTP l'connexion (Login)
Route::post('/login', [AuthController::class, 'requestLoginOtp']);

// Vérifier l'OTP (Khdama l'login w l'inscription)
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);


Route::post('/lots/liste', [LotController::class, 'liste']);
Route::post('/lots/details', [LotController::class, 'details']);
Route::post('/lots/ajouter', [LotController::class, 'ajouter']);
Route::post('/lots/modifier', [LotController::class, 'modifier']);
Route::post('/lots/supprimer', [LotController::class, 'supprimer']);

// Actions 3la les propriétaires f wsst l'lot
Route::post('/lots/proprietaire/activer', [LotController::class, 'activerProprietaire']);
Route::post('/lots/proprietaire/desactiver', [LotController::class, 'desactiverProprietaire']);
Route::post('/lots/proprietaire/supprimer', [LotController::class, 'supprimerProprietaire']);



Route::post('/coproprietaires/liste', [CoproprietaireController::class, 'liste']);
Route::post('/coproprietaires/desactiver', [CoproprietaireController::class, 'desactiver']);
Route::post('/coproprietaires/ajouter', [CoproprietaireController::class, 'ajouter']);


Route::post('/proprietes/ajouter', [CoproprieteController::class, 'ajouter']);
Route::post('/coproprietaires/supprimer', [CoproprietaireController::class, 'supprimer']);

Route::post('/cles-repartition/liste', [CleRepartitionController::class, 'liste']);
Route::post('/cles-repartition/ajouter', [CleRepartitionController::class, 'ajouter']);
Route::post('/cles-repartition/modifier', [CleRepartitionController::class, 'modifier']);
Route::post('/cles-repartition/supprimer', [CleRepartitionController::class, 'supprimer']);