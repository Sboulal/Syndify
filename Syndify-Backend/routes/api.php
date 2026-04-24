<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LotController;
use App\Http\Controllers\CoproprietaireController;
use App\Http\Controllers\CoproprieteController;
use App\Http\Controllers\CleRepartitionController;
use App\Http\Controllers\ExerciceController;
use App\Http\Controllers\BudgetOperationController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\AppelFondsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClotureController;

// ==========================================
// 🔓 ROUTES PUBLIQUES (Bla Token)
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'requestLoginOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// ==========================================
// 🔐 ROUTES SÉCURISÉES (Khasshom Token Sanctum)
// ==========================================
// Route::middleware('auth:sanctum')->group(function () {

    // --- Lots ---
    Route::post('/lots/liste', [LotController::class, 'liste']);
    Route::post('/lots/details', [LotController::class, 'details']);
    Route::post('/lots/ajouter', [LotController::class, 'ajouter']);
    Route::post('/lots/modifier', [LotController::class, 'modifier']);
    Route::post('/lots/supprimer', [LotController::class, 'supprimer']);
    Route::post('/lots/proprietaire/activer', [LotController::class, 'activerProprietaire']);
    Route::post('/lots/proprietaire/desactiver', [LotController::class, 'desactiverProprietaire']);
    Route::post('/lots/proprietaire/supprimer', [LotController::class, 'supprimerProprietaire']);

    // --- Copropriétaires ---
    Route::post('/coproprietaires/liste', [CoproprietaireController::class, 'liste']);
    Route::post('/coproprietaires/desactiver', [CoproprietaireController::class, 'desactiver']);
    Route::post('/coproprietaires/ajouter', [CoproprietaireController::class, 'ajouter']);
    Route::post('/coproprietaires/supprimer', [CoproprietaireController::class, 'supprimer']);
    // (Zedt historique hna ila knti dayraha)
    Route::post('/coproprietaires/historique', [CoproprietaireController::class, 'historique']);

    // --- Propriétés ---
    Route::post('/proprietes/ajouter', [CoproprieteController::class, 'ajouter']);

    // --- Clés de Répartition ---
    Route::post('/cles-repartition/liste', [CleRepartitionController::class, 'liste']);
    Route::post('/cles-repartition/ajouter', [CleRepartitionController::class, 'ajouter']);
    Route::post('/cles-repartition/modifier', [CleRepartitionController::class, 'modifier']);
    Route::post('/cles-repartition/supprimer', [CleRepartitionController::class, 'supprimer']);

    // --- Exercices ---
    Route::post('/exercices/liste', [ExerciceController::class, 'liste']);
    Route::post('/exercices/ajouter', [ExerciceController::class, 'ajouter']);
    Route::post('/exercices/modifier', [ExerciceController::class, 'modifier']);
    Route::post('/exercices/supprimer', [ExerciceController::class, 'supprimer']);

    // --- Budgets & Opérations ---
    Route::post('/budgets/charger', [BudgetOperationController::class, 'chargerDonnees']);
    Route::post('/budgets/releve', [BudgetOperationController::class, 'telechargerReleve']);
    Route::post('/encaissements/ajouter', [BudgetOperationController::class, 'ajouterEncaissement']);
    Route::post('/encaissements/supprimer', [BudgetOperationController::class, 'supprimerEncaissement']);
    Route::post('/depenses/ajouter', [BudgetOperationController::class, 'ajouterDepense']);
    Route::post('/depenses/supprimer', [BudgetOperationController::class, 'supprimerDepense']);

    // --- Simulation ---
    Route::post('/simulation/charger', [SimulationController::class, 'chargerDonneesSimulation']);

    // --- Appels de Fonds ---
    Route::post('/appels-fonds/liste', [AppelFondsController::class, 'liste']);
    Route::post('/appels-fonds/ajouter-planifie', [AppelFondsController::class, 'ajouterPlanifie']);
    Route::post('/appels-fonds/ajouter-exceptionnel', [AppelFondsController::class, 'ajouterExceptionnel']);
    Route::post('/appels-fonds/generer', [AppelFondsController::class, 'generer']);
    Route::post('/appels-fonds/envoyer', [AppelFondsController::class, 'envoyer']);
    Route::post('/appels-fonds/details', [AppelFondsController::class, 'details']);
    Route::post('/appels-fonds/supprimer', [AppelFondsController::class, 'supprimer']);

    Route::post('/clotures/charger', [ClotureController::class, 'charger']);
    Route::post('/clotures/enregistrer', [ClotureController::class, 'enregistrer']);
    Route::post('/clotures/finaliser', [ClotureController::class, 'finaliser']);

    // --- Dashboard ---
    Route::post('/dashboard/data', [DashboardController::class, 'getDashboardData']);   


