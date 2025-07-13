<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Contrat;
use App\Models\ConfirmationMensuelle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Récupérer les statistiques de l'employeur
     * GET /api/v1/dashboard/stats
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            // Statistiques générales
            $totalEmployees = Employee::whereHas('contrats', function($query) use ($employer) {
                $query->where('employer_id', $employer->id);
            })->count();

            $activeContracts = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->count();

            $expiredContracts = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', false)
                ->whereNotNull('date_fin')
                ->count();

            // Confirmations en attente (mois actuel)
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            $activeContractIds = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->pluck('id');

            $confirmedThisMonth = ConfirmationMensuelle::whereIn('contrat_id', $activeContractIds)
                ->where('mois', $currentMonth)
                ->where('annee', $currentYear)
                ->count();

            $pendingConfirmations = $activeContracts - $confirmedThisMonth;

            // Employés par type d'emploi
            $employeesByType = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->select('type_emploi', DB::raw('count(*) as count'))
                ->groupBy('type_emploi')
                ->pluck('count', 'type_emploi')
                ->toArray();

            // Employés par nationalité
            $employeesByNationality = Employee::whereHas('contrats', function($query) use ($employer) {
                    $query->where('employer_id', $employer->id)
                          ->where('est_actif', true);
                })
                ->join('nationalities', 'employees.nationality_id', '=', 'nationalities.id')
                ->select('nationalities.nom', DB::raw('count(*) as count'))
                ->groupBy('nationalities.nom')
                ->pluck('count', 'nom')
                ->toArray();

            // Évolution des employés sur les 6 derniers mois
            $employeeEvolution = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $month = $date->format('Y-m');
                $monthName = $date->format('M Y');
                
                $count = Contrat::where('employer_id', $employer->id)
                    ->where('date_debut', '<=', $date->endOfMonth())
                    ->where(function($query) use ($date) {
                        $query->whereNull('date_fin')
                              ->orWhere('date_fin', '>=', $date->startOfMonth());
                    })
                    ->count();
                
                $employeeEvolution[] = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'count' => $count
                ];
            }

            // Salaires moyens par type d'emploi
            $averageSalaries = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->select('type_emploi', DB::raw('AVG(salaire_mensuel) as average_salary'))
                ->groupBy('type_emploi')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type_emploi => round($item->average_salary, 0)];
                })
                ->toArray();

            // Confirmations mensuelles des 12 derniers mois
            $confirmationStats = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $month = $date->month;
                $year = $date->year;
                $monthName = $date->format('M Y');
                
                $totalContracts = Contrat::where('employer_id', $employer->id)
                    ->where('date_debut', '<=', $date->endOfMonth())
                    ->where(function($query) use ($date) {
                        $query->whereNull('date_fin')
                              ->orWhere('date_fin', '>=', $date->startOfMonth());
                    })
                    ->count();
                
                $confirmedCount = ConfirmationMensuelle::whereIn('contrat_id', function($query) use ($employer) {
                        $query->select('id')
                              ->from('contrats')
                              ->where('employer_id', $employer->id);
                    })
                    ->where('mois', $month)
                    ->where('annee', $year)
                    ->count();
                
                $confirmationRate = $totalContracts > 0 ? round(($confirmedCount / $totalContracts) * 100, 1) : 0;
                
                $confirmationStats[] = [
                    'month' => $date->format('Y-m'),
                    'month_name' => $monthName,
                    'total_contracts' => $totalContracts,
                    'confirmed' => $confirmedCount,
                    'confirmation_rate' => $confirmationRate
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées avec succès',
                'data' => [
                    'overview' => [
                        'total_employees' => $totalEmployees,
                        'active_contracts' => $activeContracts,
                        'expired_contracts' => $expiredContracts,
                        'pending_confirmations' => $pendingConfirmations,
                    ],
                    'employees_by_type' => $employeesByType,
                    'employees_by_nationality' => $employeesByNationality,
                    'employee_evolution' => $employeeEvolution,
                    'average_salaries' => $averageSalaries,
                    'confirmation_statistics' => $confirmationStats,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir un résumé rapide pour le tableau de bord
     * GET /api/v1/dashboard/summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            // Contrats actifs
            $activeContracts = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->count();

            // Confirmations en attente ce mois
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            $activeContractIds = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->pluck('id');

            $confirmedThisMonth = ConfirmationMensuelle::whereIn('contrat_id', $activeContractIds)
                ->where('mois', $currentMonth)
                ->where('annee', $currentYear)
                ->count();

            $pendingConfirmations = $activeContracts - $confirmedThisMonth;

            // Contrats expirant bientôt (dans les 30 prochains jours)
            $expiringSoon = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->whereNotNull('date_fin')
                ->where('date_fin', '<=', now()->addDays(30))
                ->where('date_fin', '>=', now())
                ->count();

            // Salaire total mensuel
            $totalMonthlySalary = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->sum('salaire_mensuel');

            // Derniers employés ajoutés
            $recentEmployees = Employee::whereHas('contrats', function($query) use ($employer) {
                    $query->where('employer_id', $employer->id);
                })
                ->with(['nationality', 'activeContrat'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'prenom' => $employee->prenom,
                        'nom' => $employee->nom,
                        'nationalite' => $employee->nationality->nom ?? null,
                        'type_emploi' => $employee->activeContrat->type_emploi ?? null,
                        'date_ajout' => $employee->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Résumé du tableau de bord récupéré avec succès',
                'data' => [
                    'active_contracts' => $activeContracts,
                    'pending_confirmations' => $pendingConfirmations,
                    'contracts_expiring_soon' => $expiringSoon,
                    'total_monthly_salary' => $totalMonthlySalary,
                    'recent_employees' => $recentEmployees,
                    'employer' => [
                        'id' => $employer->id,
                        'prenom' => $employer->prenom,
                        'nom' => $employer->nom,
                        'email' => $employer->email,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export des données d'employés
     * GET /api/v1/reports/employees
     */
    public function exportEmployees(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            // Valider les paramètres
            $format = $request->get('format', 'json'); // json, csv, pdf, excel
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = Employee::whereHas('contrats', function($q) use ($employer) {
                    $q->where('employer_id', $employer->id);
                })
                ->with(['nationality', 'contrats' => function($q) use ($employer) {
                    $q->where('employer_id', $employer->id);
                }, 'contrats.confirmationsMensuelles']);

            // Filtrer par date si spécifiée
            if ($dateFrom) {
                $query->whereHas('contrats', function($q) use ($dateFrom, $employer) {
                    $q->where('employer_id', $employer->id)
                      ->where('date_debut', '>=', $dateFrom);
                });
            }

            if ($dateTo) {
                $query->whereHas('contrats', function($q) use ($dateTo, $employer) {
                    $q->where('employer_id', $employer->id)
                      ->where('date_debut', '<=', $dateTo);
                });
            }

            $employees = $query->get();

            // Préparer les données d'export
            $exportData = $employees->map(function ($employee) {
                $activeContract = $employee->contrats->where('est_actif', true)->first();
                $totalConfirmations = $employee->contrats->sum(function ($contract) {
                    return $contract->confirmationsMensuelles->count();
                });

                return [
                    'employee_id' => str_pad($employee->id, 6, '0', STR_PAD_LEFT),
                    'prenom' => $employee->prenom,
                    'nom' => $employee->nom,
                    'genre' => $employee->genre,
                    'date_naissance' => $employee->date_naissance,
                    'nationalite' => $employee->nationality->nom ?? null,
                    'region' => $employee->region,
                    'ville' => $employee->ville,
                    'quartier' => $employee->quartier,
                    'date_arrivee' => $employee->date_arrivee,
                    'etat_civil' => $employee->etat_civil,
                    'is_active' => $employee->is_active,
                    'contrat_actuel' => $activeContract ? [
                        'type_emploi' => $activeContract->type_emploi,
                        'salaire_mensuel' => $activeContract->salaire_mensuel,
                        'date_debut' => $activeContract->date_debut,
                        'est_actif' => $activeContract->est_actif,
                    ] : null,
                    'nombre_contrats' => $employee->contrats->count(),
                    'total_confirmations' => $totalConfirmations,
                    'date_enregistrement' => $employee->created_at->toISOString(),
                ];
            });

            // Note: Pour les formats CSV, PDF, Excel, il faudrait implémenter
            // la génération de fichiers et retourner l'URL de téléchargement
            // Pour l'instant, on retourne les données JSON

            return response()->json([
                'success' => true,
                'message' => 'Export généré avec succès',
                'data' => [
                    'format' => $format,
                    'total_records' => $exportData->count(),
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'generated_at' => now()->toISOString(),
                    'employees' => $exportData,
                    // 'download_url' => 'https://api.sgre.dj/downloads/employees_export_' . time() . '.' . $format
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'export',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}