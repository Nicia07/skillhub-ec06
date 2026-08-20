<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\SsoAuthController;
use App\Http\Controllers\SsoProfileController;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Http\Middleware\Authenticate;

// ==========================================
// 1. ROUTES PUBLIQUES (Sans connexion)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Catalogue public : visible par tous, connectés ou non.
Route::get('/formations', [FormationController::class, 'index']);
// touch.activity : si un apprenant connecte accede a la formation, met a jour
// last_activity_at sur son inscription (regle de desinscription apres 30 jours d'inactivite).
Route::get('/formations/{id}', [FormationController::class, 'show'])->middleware('touch.activity');

// --- AUTHENTIFICATION FORTE DÉLÉGUÉE AU MICROSERVICE SPRING BOOT SSO ---
// Login : Laravel présente la Master Key au microservice et relaie le JWT émis.
Route::post('/sso/login', [SsoAuthController::class, 'login']);
// Route protégée : n'autorise l'accès que si le microservice SSO valide le token.
Route::get('/sso/profile', [SsoProfileController::class, 'show'])->middleware('sso');

// ==========================================
// 2. ROUTES PROTÉGÉES (Nécessitent le Token)
// ==========================================
Route::middleware([Authenticate::class])->group(function () {
    // Session
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // --- SIGNALEMENT D'UN PROBLÈME SUR UNE FORMATION ---
    Route::post('formations/{id}/signalements', [SignalementController::class, 'store']);
    Route::get('formations/{id}/signalements', [SignalementController::class, 'index']);

    // --- GESTION DU CATALOGUE FORMATEUR ---
    Route::get('my-formations', [FormationController::class, 'myFormations']);
    Route::post('formations', [FormationController::class, 'store'])->middleware('role:formateur');
    Route::put('formations/{id}', [FormationController::class, 'update'])->middleware('role:formateur');
    Route::delete('formations/{id}', [FormationController::class, 'destroy'])->middleware('role:formateur');

    // --- SUIVI DES FORMATIONS PAR L'APPRENANT ---
    Route::get('mes-inscriptions', [InscriptionController::class, 'index']);
    Route::post('inscriptions', [InscriptionController::class, 'store'])->middleware('role:apprenant');
    Route::put('inscriptions/{id}/terminer', [InscriptionController::class, 'terminer'])->middleware('role:apprenant');
    Route::delete('inscriptions/{id}', [InscriptionController::class, 'destroy'])->middleware('role:apprenant');
});
