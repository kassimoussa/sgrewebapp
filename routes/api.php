<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployerDocumentController;
use App\Http\Controllers\Api\MonthlyConfirmationController;
use App\Http\Controllers\Api\NationalityController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DashboardController;
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

// ===========================================
// API V1 - TOUTES LES ROUTES
// ===========================================
Route::prefix('v1')->group(function () {

    // ===========================================
    // ROUTES PUBLIQUES
    // ===========================================
    
    // Authentification publique
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('check-identifier', [AuthController::class, 'checkIdentifier']);
    });

    // Nationalités publiques (pour formulaires)
    Route::prefix('nationalities')->group(function () {
        Route::get('/', [NationalityController::class, 'index']);
    });

    // Health check
    Route::get('health', function () {
        return response()->json([
            'status' => 'OK',
            'message' => 'API Déclaration Employés Domestiques',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ]);
    });

    // ===========================================
    // ROUTES PROTÉGÉES (AUTH SANCTUM)
    // ===========================================
    Route::middleware('auth:sanctum')->group(function () {

        // ============ AUTHENTIFICATION ET PROFIL ============
        Route::prefix('auth')->group(function () {
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('complete-profile', [AuthController::class, 'completeProfile']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('change-password', [AuthController::class, 'changePassword']);
            Route::post('upload-profile-documents', [AuthController::class, 'uploadProfileDocuments']);
        });

        // ============ GESTION DES EMPLOYÉS ============
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index']);
            Route::post('/', [EmployeeController::class, 'store']);
            Route::post('search', [EmployeeController::class, 'search']);
            Route::get('{id}', [EmployeeController::class, 'show']);
            Route::put('{id}', [EmployeeController::class, 'update']);
            Route::put('{id}/photo', [EmployeeController::class, 'updatePhoto']);
            Route::post('{id}/register-existing', [EmployeeController::class, 'registerExisting']);
            Route::delete('{id}', [EmployeeController::class, 'destroy']);
            
            // Contrats d'un employé spécifique
            Route::get('{id}/contracts', [ContractController::class, 'getEmployeeContracts']);
            Route::post('{id}/contracts', [ContractController::class, 'createContract']);
            
            // Confirmations mensuelles d'un employé
            Route::post('{id}/monthly-confirmations', [MonthlyConfirmationController::class, 'confirmEmployee']);
        });

        // ============ GESTION DES CONTRATS ============
        Route::prefix('contracts')->group(function () {
            Route::get('/', [ContractController::class, 'index']);
            Route::get('{id}', [ContractController::class, 'show']);
            Route::put('{id}', [ContractController::class, 'update']);
            Route::put('{id}/terminate', [ContractController::class, 'terminateContract']);
        });

        // ============ CONFIRMATIONS MENSUELLES ============
        Route::prefix('monthly-confirmations')->group(function () {
            Route::get('/', [MonthlyConfirmationController::class, 'index']);
            Route::post('/', [MonthlyConfirmationController::class, 'store']);
            Route::get('pending', [MonthlyConfirmationController::class, 'pending']);
            Route::get('statistics', [MonthlyConfirmationController::class, 'statistics']);
            Route::get('{id}', [MonthlyConfirmationController::class, 'show']);
            Route::put('{id}', [MonthlyConfirmationController::class, 'update']);
            Route::delete('{id}', [MonthlyConfirmationController::class, 'destroy']);
        });

        // ============ GESTION DES DOCUMENTS ============
        
        // Documents employeur
        Route::prefix('employer/documents')->name('employer.documents.')->group(function () {
            Route::get('/', [EmployerDocumentController::class, 'index'])->name('index');
            Route::post('upload', [EmployerDocumentController::class, 'upload'])->name('upload');
            Route::get('stats', [EmployerDocumentController::class, 'getStats'])->name('stats');
            Route::get('{document}', [EmployerDocumentController::class, 'show'])
                ->name('show')->where('document', '[0-9]+');
            Route::delete('{document}', [EmployerDocumentController::class, 'destroy'])
                ->name('destroy')->where('document', '[0-9]+');
            Route::get('type/{type}', [EmployerDocumentController::class, 'getByType'])
                ->name('getByType')->where('type', 'piece_identite|justificatif_domicile|autre');
        });

        // Documents employés
        Route::prefix('documents/employee')->group(function () {
            Route::post('{employee_id}', [DocumentController::class, 'uploadEmployeeDocument']);
            Route::get('{employee_id}', [DocumentController::class, 'getEmployeeDocuments']);
            Route::get('{employee_id}/check', [DocumentController::class, 'checkEmployeeDocuments']);
            Route::delete('{document_id}', [DocumentController::class, 'deleteEmployeeDocument']);
        });

        // Documents génériques
        Route::prefix('documents')->group(function () {
            Route::get('download/{type}/{document_id}', [DocumentController::class, 'downloadDocument']);
            Route::get('statistics', [DocumentController::class, 'statistics']);
            Route::get('types', [DocumentController::class, 'getDocumentTypes']);
        });

        // ============ DASHBOARD, STATISTIQUES ET EXPORTS ============
        Route::prefix('dashboard')->group(function () {
            Route::get('stats', [DashboardController::class, 'getStats']);
            Route::get('summary', [DashboardController::class, 'getSummary']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('employees', [DashboardController::class, 'exportEmployees']);
        });

        // ============ NATIONALITÉS (ADMIN) ============
        Route::prefix('nationalities')->group(function () {
            Route::post('/', [NationalityController::class, 'store']);
            Route::get('{id}', [NationalityController::class, 'show']);
            Route::put('{id}', [NationalityController::class, 'update']);
            Route::delete('{id}', [NationalityController::class, 'destroy']);
            Route::get('statistics', [NationalityController::class, 'statistics']);
        });
    });
});