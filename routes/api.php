<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployerDocumentController;
use App\Http\Controllers\Api\MonthlyConfirmationController;
use App\Http\Controllers\Api\NationalityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques pour l'authentification
Route::prefix('v1')->group(function () {

    // Routes d'authentification (publiques)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('check-identifier', [AuthController::class, 'checkIdentifier']);
    });

    // Routes protégées par authentification Sanctum
    Route::middleware('auth:sanctum')->group(function () {

        // Routes d'authentification (protégées)
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('complete-profile', [AuthController::class, 'completeProfile']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('change-password', [AuthController::class, 'changePassword']);
        });

        // Routes pour les documents EMPLOYEUR (dédiées)
        Route::prefix('employer/documents')->name('employer.documents.')->group(function () {
            // Lister tous les documents de l'employeur connecté
            Route::get('/', [EmployerDocumentController::class, 'index'])
                ->name('index');

            // Upload d'un document employeur
            Route::post('upload', [EmployerDocumentController::class, 'upload'])
                ->name('upload');

            // Statistiques des documents employeur
            Route::get('stats', [EmployerDocumentController::class, 'getStats'])
                ->name('stats');

            // Récupérer un document employeur par son ID
            Route::get('{document}', [EmployerDocumentController::class, 'show'])
                ->name('show')
                ->where('document', '[0-9]+');

            // Supprimer un document employeur
            Route::delete('{document}', [EmployerDocumentController::class, 'destroy'])
                ->name('destroy')
                ->where('document', '[0-9]+');

            // Récupérer un document employeur par type
            Route::get('type/{type}', [EmployerDocumentController::class, 'getByType'])
                ->name('getByType')
                ->where('type', 'piece_identite|justificatif_domicile|autre');
        });

        // ===========================================
        // ROUTES POUR LES EMPLOYÉS
        // ===========================================
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index']); // Liste des employés
            Route::post('/', [EmployeeController::class, 'store']); // Créer un employé
            Route::get('{id}', [EmployeeController::class, 'show']); // Détails d'un employé
            Route::put('{id}', [EmployeeController::class, 'update']); // Modifier un employé
            Route::delete('{id}', [EmployeeController::class, 'destroy']); // Terminer le contrat d'un employé
        });

        // ===========================================
        // ROUTES POUR LES DOCUMENTS
        // ===========================================

        Route::prefix('documents/employee')->group(function () {
            Route::post('{employee_id}', [DocumentController::class, 'uploadEmployeeDocument']);
            Route::get('{employee_id}', [DocumentController::class, 'getEmployeeDocuments']);
            Route::get('{employee_id}/check', [DocumentController::class, 'checkEmployeeDocuments']);
            Route::delete('{document_id}', [DocumentController::class, 'deleteEmployeeDocument']);
        });

        Route::prefix('documents')->group(function () {
            Route::get('download/{type}/{document_id}', [DocumentController::class, 'downloadDocument']);
            Route::get('statistics', [DocumentController::class, 'statistics']);
            Route::get('types', [DocumentController::class, 'getDocumentTypes']);
        });

        // Routes pour les contrats (à implémenter plus tard)
        Route::prefix('contracts')->group(function () {
            // Route::get('/', [ContractController::class, 'index']);
            // Route::post('/', [ContractController::class, 'store']);
            // Route::get('{contract}', [ContractController::class, 'show']);
            // Route::put('{contract}', [ContractController::class, 'update']);
        });



        // ===========================================
        // ROUTES POUR LES CONFIRMATIONS MENSUELLES
        // ===========================================
        Route::prefix('monthly-confirmations')->group(function () {
            Route::get('/', [MonthlyConfirmationController::class, 'index']); // Liste des confirmations
            Route::post('/', [MonthlyConfirmationController::class, 'store']); // Créer une confirmation
            Route::get('{id}', [MonthlyConfirmationController::class, 'show']); // Détails d'une confirmation
            Route::put('{id}', [MonthlyConfirmationController::class, 'update']); // Modifier une confirmation
            Route::delete('{id}', [MonthlyConfirmationController::class, 'destroy']); // Supprimer une confirmation

            // Routes spéciales
            Route::get('pending', [MonthlyConfirmationController::class, 'pending']); // Contrats nécessitant une confirmation
            Route::get('statistics', [MonthlyConfirmationController::class, 'statistics']); // Statistiques des confirmations
        });

        // Routes pour les documents (à implémenter plus tard)
        Route::prefix('documents')->group(function () {
            // Route::post('upload', [DocumentController::class, 'upload']);
            // Route::get('{document}', [DocumentController::class, 'show']);
            // Route::delete('{document}', [DocumentController::class, 'destroy']);
        });
    });

    // ===========================================
    // ROUTES POUR LES NATIONALITÉS (Admin uniquement)
    // ===========================================
    Route::prefix('nationalities')->group(function () {
        Route::get('/', [NationalityController::class, 'index']); // Créer une nationalité
        Route::put('{id}', [NationalityController::class, 'update']); // Modifier une nationalité
        Route::delete('{id}', [NationalityController::class, 'destroy']); // Supprimer une nationalité
        Route::get('statistics', [NationalityController::class, 'statistics']); // Statistiques des nationalités
    });
});



// Route de test pour vérifier l'API
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API Déclaration Employés Domestiques',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});
