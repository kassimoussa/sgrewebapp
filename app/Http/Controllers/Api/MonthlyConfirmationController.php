<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\ConfirmationMensuelle;
use App\Models\DocumentEmployee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MonthlyConfirmationController extends Controller
{
    /**
     * Récupérer les confirmations mensuelles en attente
     * GET /api/v1/monthly-confirmations/pending
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();
            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Récupérer tous les contrats actifs de l'employeur
            $activeContracts = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->with(['employee.nationality'])
                ->get();

            // Filtrer ceux qui n'ont pas encore été confirmés ce mois
            $pendingContracts = $activeContracts->filter(function ($contract) use ($currentMonth, $currentYear) {
                return !ConfirmationMensuelle::where('contrat_id', $contract->id)
                    ->where('mois', $currentMonth)
                    ->where('annee', $currentYear)
                    ->exists();
            });

            // Préparer les données
            $pendingConfirmations = $pendingContracts->map(function ($contract) use ($currentMonth, $currentYear) {
                return [
                    'contract_id' => $contract->id,
                    'employee' => [
                        'id' => $contract->employee->id,
                        'prenom' => $contract->employee->prenom,
                        'nom' => $contract->employee->nom,
                        'nationalite' => $contract->employee->nationality->nom ?? null,
                        'photo_url' => $this->getEmployeePhotoUrl($contract->employee->id),
                    ],
                    'contract' => [
                        'type_emploi' => $contract->type_emploi,
                        'salaire_mensuel' => $contract->salaire_mensuel,
                        'date_debut' => $contract->date_debut,
                    ],
                    'confirmation_details' => [
                        'mois' => $currentMonth,
                        'annee' => $currentYear,
                        'mois_nom' => now()->format('F Y'),
                        'deadline' => now()->endOfMonth()->toISOString(),
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Confirmations en attente récupérées avec succès',
                'data' => [
                    'pending_confirmations' => $pendingConfirmations->values(),
                    'total_pending' => $pendingConfirmations->count(),
                    'current_month' => $currentMonth,
                    'current_year' => $currentYear,
                    'month_name' => now()->format('F Y'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des confirmations en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * GET /api/v1/monthly-confirmations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            $query = ConfirmationMensuelle::whereHas('contrat', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->with(['contrat.employee.nationality']);

            // Filtres
            if ($request->has('mois') && !empty($request->mois)) {
                $query->where('mois', $request->mois);
            }

            if ($request->has('annee') && !empty($request->annee)) {
                $query->where('annee', $request->annee);
            }

            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->whereHas('contrat', function($q) use ($request) {
                    $q->where('employee_id', $request->employee_id);
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $confirmations = $query->paginate($perPage);

            // Enrichir les données
            $confirmations->getCollection()->transform(function ($confirmation) {
                return [
                    'id' => $confirmation->id,
                    'mois' => $confirmation->mois,
                    'annee' => $confirmation->annee,
                    'mois_nom' => Carbon::createFromDate($confirmation->annee, $confirmation->mois, 1)->format('F Y'),
                    'date_confirmation' => $confirmation->created_at->toISOString(),
                    'commentaire' => $confirmation->commentaire,
                    'employee' => [
                        'id' => $confirmation->contrat->employee->id,
                        'prenom' => $confirmation->contrat->employee->prenom,
                        'nom' => $confirmation->contrat->employee->nom,
                        'nationalite' => $confirmation->contrat->employee->nationality->nom ?? null,
                    ],
                    'contract' => [
                        'id' => $confirmation->contrat->id,
                        'type_emploi' => $confirmation->contrat->type_emploi,
                        'salaire_mensuel' => $confirmation->contrat->salaire_mensuel,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Liste des confirmations récupérée avec succès',
                'data' => $confirmations,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des confirmations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/v1/monthly-confirmations
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contrat_id' => 'required|exists:contrats,id',
                'mois' => 'required|integer|between:1,12',
                'annee' => 'required|integer|min:2020|max:2030',
                'commentaire' => 'nullable|string|max:500',
            ], [
                'contrat_id.required' => 'Le contrat est requis.',
                'contrat_id.exists' => 'Le contrat sélectionné n\'existe pas.',
                'mois.required' => 'Le mois est requis.',
                'mois.between' => 'Le mois doit être entre 1 et 12.',
                'annee.required' => 'L\'année est requise.',
                'annee.min' => 'L\'année doit être au minimum 2020.',
                'annee.max' => 'L\'année ne peut pas dépasser 2030.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $employer = $request->user();

            // Vérifier que le contrat appartient à l'employeur
            $contrat = Contrat::where('id', $request->contrat_id)
                ->where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->with(['employee'])
                ->first();

            if (!$contrat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrat non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            // Vérifier si une confirmation existe déjà pour ce mois
            $existingConfirmation = ConfirmationMensuelle::where('contrat_id', $request->contrat_id)
                ->where('mois', $request->mois)
                ->where('annee', $request->annee)
                ->first();

            if ($existingConfirmation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une confirmation existe déjà pour ce mois',
                ], 400);
            }

            // Créer la confirmation
            $confirmation = ConfirmationMensuelle::create([
                'contrat_id' => $request->contrat_id,
                'mois' => $request->mois,
                'annee' => $request->annee,
                'commentaire' => $request->commentaire,
            ]);

            $confirmation->load(['contrat.employee.nationality']);

            return response()->json([
                'success' => true,
                'message' => 'Confirmation mensuelle créée avec succès',
                'data' => [
                    'confirmation' => [
                        'id' => $confirmation->id,
                        'mois' => $confirmation->mois,
                        'annee' => $confirmation->annee,
                        'mois_nom' => Carbon::createFromDate($confirmation->annee, $confirmation->mois, 1)->format('F Y'),
                        'commentaire' => $confirmation->commentaire,
                        'date_confirmation' => $confirmation->created_at->toISOString(),
                    ],
                    'employee' => [
                        'id' => $contrat->employee->id,
                        'prenom' => $contrat->employee->prenom,
                        'nom' => $contrat->employee->nom,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la confirmation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmer qu'un employé travaille toujours (endpoint direct)
     * POST /api/v1/employees/{id}/monthly-confirmations
     */
    public function confirmEmployee(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();

            // Trouver le contrat actif de l'employé avec cet employeur
            $contrat = Contrat::where('employee_id', $id)
                ->where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->with(['employee.nationality'])
                ->first();

            if (!$contrat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun contrat actif trouvé pour cet employé',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'mois' => 'required|integer|between:1,12',
                'annee' => 'required|integer|min:2020|max:2030',
                'commentaire' => 'nullable|string|max:500',
            ], [
                'mois.required' => 'Le mois est requis.',
                'mois.between' => 'Le mois doit être entre 1 et 12.',
                'annee.required' => 'L\'année est requise.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Vérifier si une confirmation existe déjà
            $existingConfirmation = ConfirmationMensuelle::where('contrat_id', $contrat->id)
                ->where('mois', $request->mois)
                ->where('annee', $request->annee)
                ->first();

            if ($existingConfirmation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé a déjà été confirmé pour ce mois',
                ], 400);
            }

            // Créer la confirmation
            $confirmation = ConfirmationMensuelle::create([
                'contrat_id' => $contrat->id,
                'mois' => $request->mois,
                'annee' => $request->annee,
                'commentaire' => $request->commentaire ?? 'Employé présent et satisfaisant',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employé confirmé avec succès',
                'data' => [
                    'confirmation' => [
                        'id' => $confirmation->id,
                        'mois' => $confirmation->mois,
                        'annee' => $confirmation->annee,
                        'mois_nom' => Carbon::createFromDate($confirmation->annee, $confirmation->mois, 1)->format('F Y'),
                        'commentaire' => $confirmation->commentaire,
                        'date_confirmation' => $confirmation->created_at->toISOString(),
                    ],
                    'employee' => [
                        'id' => $contrat->employee->id,
                        'prenom' => $contrat->employee->prenom,
                        'nom' => $contrat->employee->nom,
                        'nationalite' => $contrat->employee->nationality->nom ?? null,
                    ],
                    'contract' => [
                        'id' => $contrat->id,
                        'type_emploi' => $contrat->type_emploi,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation de l\'employé',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/v1/monthly-confirmations/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();

            $confirmation = ConfirmationMensuelle::whereHas('contrat', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->with(['contrat.employee.nationality'])->find($id);

            if (!$confirmation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirmation non trouvée ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Détails de la confirmation récupérés avec succès',
                'data' => [
                    'confirmation' => [
                        'id' => $confirmation->id,
                        'mois' => $confirmation->mois,
                        'annee' => $confirmation->annee,
                        'mois_nom' => Carbon::createFromDate($confirmation->annee, $confirmation->mois, 1)->format('F Y'),
                        'commentaire' => $confirmation->commentaire,
                        'date_confirmation' => $confirmation->created_at->toISOString(),
                    ],
                    'employee' => [
                        'id' => $confirmation->contrat->employee->id,
                        'prenom' => $confirmation->contrat->employee->prenom,
                        'nom' => $confirmation->contrat->employee->nom,
                        'nationalite' => $confirmation->contrat->employee->nationality->nom ?? null,
                    ],
                    'contract' => [
                        'id' => $confirmation->contrat->id,
                        'type_emploi' => $confirmation->contrat->type_emploi,
                        'salaire_mensuel' => $confirmation->contrat->salaire_mensuel,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la confirmation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/v1/monthly-confirmations/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();

            $confirmation = ConfirmationMensuelle::whereHas('contrat', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->find($id);

            if (!$confirmation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirmation non trouvée ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'commentaire' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $confirmation->update([
                'commentaire' => $request->commentaire,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Confirmation mise à jour avec succès',
                'data' => [
                    'confirmation' => [
                        'id' => $confirmation->id,
                        'mois' => $confirmation->mois,
                        'annee' => $confirmation->annee,
                        'commentaire' => $confirmation->commentaire,
                        'updated_at' => $confirmation->updated_at->toISOString(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/v1/monthly-confirmations/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();

            $confirmation = ConfirmationMensuelle::whereHas('contrat', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->find($id);

            if (!$confirmation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirmation non trouvée ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            $confirmation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Confirmation supprimée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiques des confirmations
     * GET /api/v1/monthly-confirmations/statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            // Statistiques générales
            $totalConfirmations = ConfirmationMensuelle::whereHas('contrat', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->count();

            $currentMonth = now()->month;
            $currentYear = now()->year;

            $confirmationsThisMonth = ConfirmationMensuelle::whereHas('contrat', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->where('mois', $currentMonth)->where('annee', $currentYear)->count();

            $activeContracts = Contrat::where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->count();

            $confirmationRate = $activeContracts > 0 ? round(($confirmationsThisMonth / $activeContracts) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des confirmations récupérées avec succès',
                'data' => [
                    'total_confirmations' => $totalConfirmations,
                    'confirmations_this_month' => $confirmationsThisMonth,
                    'active_contracts' => $activeContracts,
                    'confirmation_rate' => $confirmationRate,
                    'current_month' => $currentMonth,
                    'current_year' => $currentYear,
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
     * Obtenir l'URL de la photo d'un employé
     */
    private function getEmployeePhotoUrl($employeeId)
    {
        $photo = DocumentEmployee::where('employee_id', $employeeId)
            ->where('type_document', 'photo')
            ->latest()
            ->first();

        return $photo ? Storage::url($photo->chemin_fichier) : null;
    }
}