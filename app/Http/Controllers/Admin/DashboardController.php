<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\Contrat;
use App\Models\Nationality;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Afficher le dashboard admin
     */
    public function index(): View
    {
        $stats = [
            'total_employers' => Employer::count(),
            'total_employees' => Employee::count(),
            'active_contracts' => Contrat::where('est_actif', true)->count(),
            'total_nationalities' => Nationality::count(),
        ];

        // Derniers employeurs inscrits
        $recent_employers = Employer::latest()->take(5)->get();
        
        // Derniers employés ajoutés
        $recent_employees = Employee::with('nationality')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recent_employers', 'recent_employees'));
    }
}