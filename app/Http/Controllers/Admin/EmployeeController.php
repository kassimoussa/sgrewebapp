<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Services\AttestationService;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Afficher la liste des employés
     */
    public function index(): View
    {
        return view('admin.employees.index');
    }

    /**
     * Afficher les détails d'un employé
     */
    public function show(Employee $employee): View
    {
        // Eager loading des relations nécessaires pour optimiser les performances
        $employee->load([
            'nationality',
            'contrats' => function($query) {
                $query->with(['employer', 'confirmations'])
                      ->orderBy('created_at', 'desc');
            },
            'activeContrat.employer',
            'documents' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'photo',
            'identityDocument'
        ]);

        // Statistiques de l'employé pour affichage rapide
        $stats = [
            'total_contracts' => $employee->contrats->count(),
            'active_contracts' => $employee->contrats->where('est_actif', true)->count(),
            'total_employers' => $employee->contrats->unique('employer_id')->count(),
            'total_confirmations' => $employee->contrats->sum(function($contrat) {
                return $contrat->confirmations->count();
            }),
            'current_salary' => $employee->activeContrat?->salaire_mensuel ?? 0,
            'documents_count' => $employee->documents->count(),
            'last_activity' => $employee->updated_at,
            'employment_duration' => $employee->contrats->sum(function($contrat) {
                $startDate = $contrat->date_debut;
                $endDate = $contrat->date_fin ?? now();
                return $startDate->diffInMonths($endDate);
            }),
        ];

        return view('admin.employees.show', compact('employee', 'stats'));
    }


    /**
     * Télécharger l'attestation d'un employé
     */
    public function downloadAttestation(Employee $employee)
    {
        try {
            $attestationService = new AttestationService();
            
            if (!$attestationService->hasValidAttestation($employee)) {
                return back()->with('error', 'Aucune attestation valide trouvée pour cet employé.');
            }

            $attestation = $employee->documents()
                ->where('type_document', 'attestation_identite')
                ->latest()
                ->first();

            if (!$attestation || !Storage::disk('public')->exists($attestation->chemin_fichier)) {
                return back()->with('error', 'Le fichier d\'attestation est introuvable.');
            }

            $filePath = storage_path('app/public/' . $attestation->chemin_fichier);
            $fileName = "attestation_" . date('dmY') . ".pdf";

            return response()->download($filePath, $fileName);

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du téléchargement: ' . $e->getMessage());
        }
    }


    /**
     * Télécharger le permis de travail d'un employé
     */
    public function downloadWorkPermit(Employee $employee)
    {
        try {
            $attestationService = new AttestationService();
            
            if (!$attestationService->hasValidWorkPermit($employee)) {
                return back()->with('error', 'Aucun permis de travail valide trouvé pour cet employé.');
            }

            $permit = $employee->documents()
                ->where('type_document', 'permis_travail')
                ->latest()
                ->first();

            if (!$permit || !Storage::disk('public')->exists($permit->chemin_fichier)) {
                return back()->with('error', 'Le fichier de permis de travail est introuvable.');
            }

            $filePath = storage_path('app/public/' . $permit->chemin_fichier);
            $fileName = "permis_" . date('dmY') . ".pdf";

            return response()->download($filePath, $fileName);

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du téléchargement: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir le statut des documents d'un employé (AJAX)
     */
    public function getDocumentStatus(Employee $employee)
    {
        try {
            $attestationService = new AttestationService();
            
            $status = [
                'has_passport' => $employee->hasPassport(),
                'has_identity_document' => $employee->hasIdentityDocument(),
                'document_status' => $employee->getDocumentStatus(),
                'needs_attestation' => $employee->needsIdentityAttestation(),
                'has_valid_attestation' => $attestationService->hasValidAttestation($employee),
                'attestation_url' => $attestationService->getValidAttestationUrl($employee),
            ];

            // Déterminer les actions possibles
            $actions = [];
            
            if ($employee->hasPassport()) {
                $actions[] = [
                    'type' => 'success',
                    'message' => 'Employé avec passeport - Éligible pour permis renouvelable',
                    'button' => null
                ];
            } elseif ($attestationService->hasValidAttestation($employee)) {
                $actions[] = [
                    'type' => 'warning',
                    'message' => 'Attestation valide - Employé doit obtenir un passeport avant expiration',
                    'button' => [
                        'text' => 'Télécharger Attestation',
                        'url' => route('admin.employees.download-attestation', $employee),
                        'class' => 'btn btn-outline-primary btn-sm'
                    ]
                ];
            } elseif ($employee->hasIdentityDocument()) {
                $actions[] = [
                    'type' => 'info',
                    'message' => 'Pièce d\'identité disponible - Peut générer une attestation',
                    'button' => [
                        'text' => 'Générer Attestation',
                        'url' => route('admin.employees.generate-attestation', $employee),
                        'class' => 'btn btn-primary btn-sm'
                    ]
                ];
            } else {
                $actions[] = [
                    'type' => 'danger',
                    'message' => 'Aucun document - Doit fournir une pièce d\'identité',
                    'button' => null
                ];
            }

            $status['actions'] = $actions;

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Activer/désactiver un employé (méthode AJAX)
     */
    public function toggleStatus(Request $request, Employee $employee)
    {
        $employee->update([
            'is_active' => !$employee->is_active
        ]);

        $message = $employee->is_active 
            ? 'Employé activé avec succès' 
            : 'Employé désactivé avec succès';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_active' => $employee->is_active
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Obtenir les statistiques d'un employé (API pour composant Livewire)
     */
    public function getStats(Employee $employee)
    {
        $stats = [
            'contracts_by_month' => $employee->contrats()
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            
            'confirmations_by_month' => $employee->contrats()
                ->join('confirmations_mensuelles', 'contrats.id', '=', 'confirmations_mensuelles.contrat_id')
                ->selectRaw('confirmations_mensuelles.annee as year, confirmations_mensuelles.mois as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            
            'salary_evolution' => $employee->contrats()
                ->selectRaw('DATE(created_at) as date, salaire_mensuel')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get(),

            'employment_timeline' => $employee->contrats()
                ->with('employer')
                ->selectRaw('id, employer_id, date_debut, date_fin, salaire_mensuel, type_emploi, est_actif')
                ->orderBy('date_debut', 'desc')
                ->get(),
        ];

        return response()->json($stats);
    }
}