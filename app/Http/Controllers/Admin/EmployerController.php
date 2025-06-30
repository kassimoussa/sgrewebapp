<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerController extends Controller
{
    /**
     * Afficher la liste des employeurs
     */
    public function index(): View
    {
        return view('admin.employers.index');
    }

    /**
     * Afficher les détails d'un employeur
     */
    public function show(Employer $employer): View
    {
        // Eager loading des relations nécessaires pour optimiser les performances
        $employer->load([
            'contrats' => function($query) {
                $query->with(['employee.nationality', 'confirmations'])
                      ->orderBy('created_at', 'desc');
            },
            'documents' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        // Statistiques de l'employeur pour affichage rapide
        $stats = [
            'total_contracts' => $employer->contrats->count(),
            'active_contracts' => $employer->contrats->where('est_actif', true)->count(),
            'total_employees' => $employer->contrats->unique('employee_id')->count(),
            'total_confirmations' => $employer->contrats->sum(function($contrat) {
                return $contrat->confirmations->count();
            }),
            'monthly_salary_total' => $employer->contrats->where('est_actif', true)->sum('salaire_mensuel'),
            'documents_count' => $employer->documents->count(),
            'last_activity' => $employer->updated_at,
        ];

        return view('admin.employers.show', compact('employer', 'stats'));
    }

    /**
     * Activer/désactiver un employeur (méthode AJAX)
     */
    public function toggleStatus(Request $request, Employer $employer)
    {
        $employer->update([
            'is_active' => !$employer->is_active
        ]);

        $message = $employer->is_active 
            ? 'Employeur activé avec succès' 
            : 'Employeur désactivé avec succès';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_active' => $employer->is_active
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Obtenir les statistiques d'un employeur (API pour composant Livewire)
     */
    public function getStats(Employer $employer)
    {
        $stats = [
            'contracts_by_month' => $employer->contrats()
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            
            'confirmations_by_month' => $employer->contrats()
                ->join('confirmations_mensuelles', 'contrats.id', '=', 'confirmations_mensuelles.contrat_id')
                ->selectRaw('confirmations_mensuelles.annee as year, confirmations_mensuelles.mois as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            
            'salary_evolution' => $employer->contrats()
                ->selectRaw('DATE(created_at) as date, SUM(salaire_mensuel) as total_salary')
                ->where('est_actif', true)
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get(),
        ];

        return response()->json($stats);
    }
}