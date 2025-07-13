<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

// Redirection vers login
Route::get('/', function () {
    return redirect()->route('login');
});

// Routes d'authentification
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/connect', [LoginController::class, 'connect'])->name('connect');
});

// Route pour mot de passe oublié (si vous l'implémentez plus tard)
Route::get('/password/request', function () {
    return view('auth.forgot-password');
})->name('password.request');

// Routes admin (protégées par auth)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard (sans prefix admin pour correspondre au layout)
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Routes employés
    Route::prefix('/admin/employees')->name('employees.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EmployeeController::class, 'index'])->name('index');
        Route::get('/{employee}', [App\Http\Controllers\Admin\EmployeeController::class, 'show'])->name('show');
        Route::patch('/{employee}/toggle-status', [App\Http\Controllers\Admin\EmployeeController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{employee}/stats', [App\Http\Controllers\Admin\EmployeeController::class, 'getStats'])->name('stats');
    });

    // Routes employeurs
    Route::prefix('/admin/employers')->name('employers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EmployerController::class, 'index'])->name('index');
        Route::get('/{employer}', [App\Http\Controllers\Admin\EmployerController::class, 'show'])->name('show');
        Route::patch('/{employer}/toggle-status', [App\Http\Controllers\Admin\EmployerController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{employer}/stats', [App\Http\Controllers\Admin\EmployerController::class, 'getStats'])->name('stats');
    });

    Route::get('/admin/contrats', function () {
        return view('admin.contrats.index');
    })->name('contrats.index');

    Route::get('/admin/users', function () {
        return view('admin.users.index');
    })->name('users.index');

    Route::get('/admin/statistics', function () {
        return view('admin.statistics.index');
    })->name('statistics.index');

    Route::get('/admin/nationalities', function () {
        return view('admin.nationalities.index');
    })->name('nationalities.index');

    Route::get('/admin/profile', function () {
        return view('admin.profile');
    })->name('profile.show');
});
