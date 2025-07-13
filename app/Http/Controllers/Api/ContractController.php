<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Contrat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContractController extends Controller
{
    /**
     * Récupérer l'historique des contrats d'un employé
     * GET /api/v1/employees/{id}/contracts
     */
    public function getEmployeeContracts(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $employee = Employee::whereHas('contrats', function($query) use ($employer) {
                $query->where('employer_id', $employer->id);
            })->find($id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            // Récupérer tous les contrats de cet employé avec cet employeur
            $contracts = Contrat::where('employee_id', $id)
                ->where('employer_id', $employer->id)
                ->with(['employer'])
                ->orderBy('date_debut', 'desc')
                ->get();

            // Enrichir les données
            $contracts->transform(function ($contract) {
                return [
                    'id' => $contract->id,
                    'type_emploi' => $contract->type_emploi,
                    'salaire_mensuel' => $contract->salaire_mensuel,
                    'date_debut' => $contract->date_debut,
                    'date_fin' => $contract->date_fin,
                    'est_actif' => $contract->est_actif,
                    'motif_fin' => $contract->motif_fin,
                    'duree_mois' => $contract->date_fin ? 
                        Carbon::parse($contract->date_debut)->diffInMonths(Carbon::parse($contract->date_fin)) :
                        Carbon::parse($contract->date_debut)->diffInMonths(now()),
                    'created_at' => $contract->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Historique des contrats récupéré avec succès',
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'prenom' => $employee->prenom,
                        'nom' => $employee->nom,
                    ],
                    'contracts' => $contracts,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des contrats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau contrat pour un employé existant
     * POST /api/v1/employees/{id}/contracts
     */
    public function createContract(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $employee = Employee::find($id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé',
                ], 404);
            }

            // Vérifier si l'employé a déjà un contrat actif avec cet employeur
            $activeContract = Contrat::where('employee_id', $id)
                ->where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->first();

            if ($activeContract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé a déjà un contrat actif avec vous',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'type_emploi' => 'required|in:Ménage,Gardien,Jardinier,Coulis,Vendeur',
                'salaire_mensuel' => 'required|numeric|min:10000|max:500000',
                'date_debut' => 'required|date|after_or_equal:today',
            ], [
                'type_emploi.required' => 'Le type d\'emploi est requis.',
                'type_emploi.in' => 'Le type d\'emploi sélectionné n\'est pas valide.',
                'salaire_mensuel.required' => 'Le salaire mensuel est requis.',
                'salaire_mensuel.min' => 'Le salaire doit être d\'au moins 10 000 FDJ.',
                'salaire_mensuel.max' => 'Le salaire ne peut pas dépasser 500 000 FDJ.',
                'date_debut.required' => 'La date de début est requise.',
                'date_debut.after_or_equal' => 'La date de début doit être aujourd\'hui ou dans le futur.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Créer le nouveau contrat
            $contract = Contrat::create([
                'employer_id' => $employer->id,
                'employee_id' => $employee->id,
                'date_debut' => $request->date_debut,
                'type_emploi' => $request->type_emploi,
                'salaire_mensuel' => $request->salaire_mensuel,
                'est_actif' => true,
            ]);

            // Activer l'employé si nécessaire
            if (!$employee->is_active) {
                $employee->update(['is_active' => true]);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $contract->load(['employer', 'employee']);

            return response()->json([
                'success' => true,
                'message' => 'Contrat créé avec succès',
                'data' => [
                    'contract' => [
                        'id' => $contract->id,
                        'type_emploi' => $contract->type_emploi,
                        'salaire_mensuel' => $contract->salaire_mensuel,
                        'date_debut' => $contract->date_debut,
                        'date_fin' => $contract->date_fin,
                        'est_actif' => $contract->est_actif,
                        'created_at' => $contract->created_at->toISOString(),
                    ],
                    'employee' => [
                        'id' => $employee->id,
                        'prenom' => $employee->prenom,
                        'nom' => $employee->nom,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du contrat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Terminer un contrat actif
     * PUT /api/v1/contracts/{id}/terminate
     */
    public function terminateContract(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $contract = Contrat::where('id', $id)
                ->where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->with(['employee'])
                ->first();

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrat non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'date_fin' => 'required|date|after_or_equal:' . $contract->date_debut,
                'motif' => 'required|string|max:500',
            ], [
                'date_fin.required' => 'La date de fin est requise.',
                'date_fin.after_or_equal' => 'La date de fin doit être après la date de début du contrat.',
                'motif.required' => 'Le motif de fin de contrat est requis.',
                'motif.max' => 'Le motif ne peut pas dépasser 500 caractères.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Mettre à jour le contrat
            $contract->update([
                'date_fin' => $request->date_fin,
                'motif_fin' => $request->motif,
                'est_actif' => false,
            ]);

            // Vérifier si l'employé a d'autres contrats actifs
            $hasActiveContracts = Contrat::where('employee_id', $contract->employee_id)
                ->where('est_actif', true)
                ->exists();

            // Si pas d'autres contrats actifs, désactiver l'employé
            if (!$hasActiveContracts) {
                $contract->employee->update(['is_active' => false]);
            }

            DB::commit();

            // Calculer la durée du contrat
            $dureeMois = Carbon::parse($contract->date_debut)->diffInMonths(Carbon::parse($contract->date_fin));

            return response()->json([
                'success' => true,
                'message' => 'Contrat terminé avec succès',
                'data' => [
                    'contract' => [
                        'id' => $contract->id,
                        'type_emploi' => $contract->type_emploi,
                        'salaire_mensuel' => $contract->salaire_mensuel,
                        'date_debut' => $contract->date_debut,
                        'date_fin' => $contract->date_fin,
                        'motif_fin' => $contract->motif_fin,
                        'est_actif' => $contract->est_actif,
                        'duree_mois' => $dureeMois,
                    ],
                    'employee' => [
                        'id' => $contract->employee->id,
                        'prenom' => $contract->employee->prenom,
                        'nom' => $contract->employee->nom,
                        'is_active' => $contract->employee->is_active,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la terminaison du contrat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lister tous les contrats de l'employeur
     * GET /api/v1/contracts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $query = Contrat::where('employer_id', $employer->id)
                ->with(['employee.nationality']);

            // Filtrage par statut
            if ($request->has('status')) {
                $status = $request->status;
                if ($status === 'active') {
                    $query->where('est_actif', true);
                } elseif ($status === 'terminated') {
                    $query->where('est_actif', false);
                }
            }

            // Filtrage par employé
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Tri
            $sortBy = $request->get('sort_by', 'date_debut');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $contracts = $query->paginate($perPage);

            // Transformer les données
            $contracts->getCollection()->transform(function ($contract) {
                $dureeJours = Carbon::parse($contract->date_debut)->diffInDays($contract->date_fin ?: now());
                $dureeMois = Carbon::parse($contract->date_debut)->diffInMonths($contract->date_fin ?: now());

                return [
                    'id' => $contract->id,
                    'type_emploi' => $contract->type_emploi,
                    'salaire_mensuel' => $contract->salaire_mensuel,
                    'date_debut' => $contract->date_debut,
                    'date_fin' => $contract->date_fin,
                    'motif_fin' => $contract->motif_fin,
                    'est_actif' => $contract->est_actif,
                    'duree_jours' => $dureeJours,
                    'duree_mois' => $dureeMois,
                    'employee' => [
                        'id' => $contract->employee->id,
                        'prenom' => $contract->employee->prenom,
                        'nom' => $contract->employee->nom,
                        'nationalite' => $contract->employee->nationality->nom ?? null,
                    ],
                    'created_at' => $contract->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Liste des contrats récupérée avec succès',
                'data' => $contracts,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des contrats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier un contrat existant
     * PUT /api/v1/contracts/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $contract = Contrat::where('id', $id)
                ->where('employer_id', $employer->id)
                ->where('est_actif', true)
                ->with(['employee'])
                ->first();

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrat non trouvé, inactif ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'type_emploi' => 'sometimes|required|in:Ménage,Gardien,Jardinier,Coulis,Vendeur',
                'salaire_mensuel' => 'sometimes|required|numeric|min:10000|max:500000',
                'date_debut' => 'sometimes|required|date',
            ], [
                'type_emploi.in' => 'Le type d\'emploi sélectionné n\'est pas valide.',
                'salaire_mensuel.min' => 'Le salaire doit être d\'au moins 10 000 FDJ.',
                'salaire_mensuel.max' => 'Le salaire ne peut pas dépasser 500 000 FDJ.',
                'date_debut.date' => 'La date de début doit être une date valide.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Vérifier si la nouvelle date de début n'est pas antérieure à d'éventuelles confirmations
            if ($request->has('date_debut')) {
                $hasConfirmations = DB::table('confirmations_mensuelles')
                    ->where('contrat_id', $contract->id)
                    ->where(function($query) use ($request) {
                        $newDate = Carbon::parse($request->date_debut);
                        $query->where('annee', '<', $newDate->year)
                              ->orWhere(function($q) use ($newDate) {
                                  $q->where('annee', '=', $newDate->year)
                                    ->where('mois', '<', $newDate->month);
                              });
                    })
                    ->exists();

                if ($hasConfirmations) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de modifier la date de début car des confirmations mensuelles existent déjà pour des périodes antérieures.',
                    ], 400);
                }
            }

            DB::beginTransaction();

            $updateData = [];
            if ($request->has('type_emploi')) {
                $updateData['type_emploi'] = $request->type_emploi;
            }
            if ($request->has('salaire_mensuel')) {
                $updateData['salaire_mensuel'] = $request->salaire_mensuel;
            }
            if ($request->has('date_debut')) {
                $updateData['date_debut'] = $request->date_debut;
            }

            $contract->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contrat modifié avec succès',
                'data' => [
                    'contract' => [
                        'id' => $contract->id,
                        'type_emploi' => $contract->type_emploi,
                        'salaire_mensuel' => $contract->salaire_mensuel,
                        'date_debut' => $contract->date_debut,
                        'date_fin' => $contract->date_fin,
                        'est_actif' => $contract->est_actif,
                        'updated_at' => $contract->updated_at->toISOString(),
                    ],
                    'employee' => [
                        'id' => $contract->employee->id,
                        'prenom' => $contract->employee->prenom,
                        'nom' => $contract->employee->nom,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du contrat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'un contrat spécifique
     * GET /api/v1/contracts/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $contract = Contrat::where('id', $id)
                ->where('employer_id', $employer->id)
                ->with(['employee.nationality', 'employer'])
                ->first();

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrat non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            // Calculer la durée
            $dureeJours = Carbon::parse($contract->date_debut)->diffInDays($contract->date_fin ?: now());
            $dureeMois = Carbon::parse($contract->date_debut)->diffInMonths($contract->date_fin ?: now());

            // Récupérer les confirmations mensuelles
            $confirmations = DB::table('confirmations_mensuelles')
                ->where('contrat_id', $contract->id)
                ->orderBy('annee', 'desc')
                ->orderBy('mois', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Détails du contrat récupérés avec succès',
                'data' => [
                    'contract' => [
                        'id' => $contract->id,
                        'type_emploi' => $contract->type_emploi,
                        'salaire_mensuel' => $contract->salaire_mensuel,
                        'date_debut' => $contract->date_debut,
                        'date_fin' => $contract->date_fin,
                        'motif_fin' => $contract->motif_fin,
                        'est_actif' => $contract->est_actif,
                        'duree_jours' => $dureeJours,
                        'duree_mois' => $dureeMois,
                        'created_at' => $contract->created_at->toISOString(),
                    ],
                    'employee' => [
                        'id' => $contract->employee->id,
                        'prenom' => $contract->employee->prenom,
                        'nom' => $contract->employee->nom,
                        'nationalite' => $contract->employee->nationality->nom ?? null,
                    ],
                    'confirmations' => $confirmations,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails du contrat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}