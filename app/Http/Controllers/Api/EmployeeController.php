<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Contrat;
use App\Models\DocumentEmployee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/v1/employees
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $query = Employee::query()
                ->join('contrats', 'employees.id', '=', 'contrats.employee_id')
                ->where('contrats.employer_id', $employer->id)
                ->where('contrats.est_actif', true)
                ->with(['nationality', 'activeContrat.employer', 'documents'])
                ->select('employees.*');

            // Recherche
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('employees.prenom', 'like', "%{$search}%")
                      ->orWhere('employees.nom', 'like', "%{$search}%")
                      ->orWhereHas('nationality', function($nq) use ($search) {
                          $nq->where('nom', 'like', "%{$search}%");
                      });
                });
            }

            // Filtres
            if ($request->has('region') && !empty($request->region)) {
                $query->where('employees.region', $request->region);
            }

            if ($request->has('genre') && !empty($request->genre)) {
                $query->where('employees.genre', $request->genre);
            }

            if ($request->has('nationality_id') && !empty($request->nationality_id)) {
                $query->where('employees.nationality_id', $request->nationality_id);
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy("employees.{$sortBy}", $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $employees = $query->paginate($perPage);

            // Enrichir les données
            $employees->getCollection()->transform(function ($employee) {
                $employee->photo_url = $this->getEmployeePhotoUrl($employee->id);
                $employee->age = Carbon::parse($employee->date_naissance)->age;
                $employee->needs_confirmation = $this->needsMonthlyConfirmation($employee->activeContrat);
                return $employee;
            });

            return response()->json([
                'success' => true,
                'message' => 'Liste des employés récupérée avec succès',
                'data' => $employees,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des employés',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/v1/employees
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Informations personnelles
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'genre' => 'required|in:Homme,Femme',
            'etat_civil' => 'required|in:Célibataire,Marié(e),Divorcé(e),Veuf(ve)',
            'date_naissance' => 'required|date|before:' . Carbon::now()->subYears(16)->format('Y-m-d'),
            'nationality_id' => 'required|exists:nationalities,id',
            'date_arrivee' => 'required|date|before_or_equal:today',
            
            // Adresse
            'region' => 'required|string|max:100',
            'ville' => 'required|string|max:100',
            'quartier' => 'required|string|max:100',
            'adresse_complete' => 'required|string|max:255',
            
            // Contrat
            'type_emploi' => 'required|in:Temps plein,Temps partiel,Journalier,Gardiennage',
            'salaire_mensuel' => 'required|numeric|min:10000|max:500000',
            'date_debut' => 'required|date|before_or_equal:today',
            
            // Documents (base64 ou fichiers)
            'photo' => 'string', // Base64 de l'image
            'piece_identite' => 'string', // Base64 du document
        ], [
            'prenom.required' => 'Le prénom est requis.',
            'nom.required' => 'Le nom est requis.',
            'date_naissance.before' => 'L\'employé doit avoir au moins 16 ans.',
            'nationality_id.exists' => 'La nationalité sélectionnée n\'existe pas.',
            'date_arrivee.before_or_equal' => 'La date d\'arrivée ne peut pas être dans le futur.',
            'salaire_mensuel.min' => 'Le salaire doit être d\'au moins 10 000 FDJ.',
            'date_debut.before_or_equal' => 'La date de début doit être aujourd\'hui ou dans le futur.',
            /* 'photo.required' => 'La photo de l\'employé est requise.',
            'piece_identite.required' => 'La pièce d\'identité est requise.', */
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $employer = $request->user();

            // 1. Créer l'employé
            $employee = Employee::create([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'genre' => $request->genre,
                'etat_civil' => $request->etat_civil,
                'date_naissance' => $request->date_naissance,
                'nationality_id' => $request->nationality_id,
                'date_arrivee' => $request->date_arrivee,
                'region' => $request->region,
                'ville' => $request->ville,
                'quartier' => $request->quartier,
                'adresse_complete' => $request->adresse_complete,
                'is_active' => true,
            ]);

            // 2. Créer le contrat
            $contrat = Contrat::create([
                'employer_id' => $employer->id,
                'employee_id' => $employee->id,
                'date_debut' => $request->date_debut,
                'type_emploi' => $request->type_emploi,
                'salaire_mensuel' => $request->salaire_mensuel,
                'est_actif' => true,
            ]);

            // 3. Sauvegarder les documents
            if ($request->photo) {
                $this->saveBase64Document($employee->id, 'photo', $request->photo);
            }

            if ($request->piece_identite) {
                $this->saveBase64Document($employee->id, 'piece_identite', $request->piece_identite);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $employee->load(['nationality', 'activeContrat.employer']);
            $employee->photo_url = $this->getEmployeePhotoUrl($employee->id);

            return response()->json([
                'success' => true,
                'message' => 'Employé enregistré avec succès',
                'data' => [
                    'employee' => $employee,
                    'contract' => $contrat,
                    'employee_id' => str_pad($employee->id, 6, '0', STR_PAD_LEFT),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de l\'employé',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/v1/employees/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $employee = Employee::with(['nationality', 'activeContrat.employer', 'documents'])
                ->whereHas('contrats', function($query) use ($employer) {
                    $query->where('employer_id', $employer->id);
                })
                ->find($id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            // Enrichir les données
            $employee->photo_url = $this->getEmployeePhotoUrl($employee->id);
            $employee->identity_document_url = $this->getEmployeeDocumentUrl($employee->id, 'piece_identite');
            $employee->age = Carbon::parse($employee->date_naissance)->age;
            $employee->needs_confirmation = $this->needsMonthlyConfirmation($employee->activeContrat);

            // Charger les confirmations mensuelles
            $confirmations = [];
            if ($employee->activeContrat) {
                $confirmations = DB::table('confirmations_mensuelles')
                    ->where('contrat_id', $employee->activeContrat->id)
                    ->orderBy('annee', 'desc')
                    ->orderBy('mois', 'desc')
                    ->limit(12)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Détails de l\'employé récupérés avec succès',
                'data' => [
                    'employee' => $employee,
                    'confirmations' => $confirmations,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/v1/employees/{id}
     */
    public function update(Request $request, $id): JsonResponse
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

            $validator = Validator::make($request->all(), [
                'prenom' => 'sometimes|required|string|max:100',
                'nom' => 'sometimes|required|string|max:100',
                'genre' => 'sometimes|required|in:Homme,Femme',
                'etat_civil' => 'sometimes|required|in:Célibataire,Marié(e),Divorcé(e),Veuf(ve)',
                'region' => 'sometimes|required|string|max:100',
                'ville' => 'sometimes|required|string|max:100',
                'quartier' => 'sometimes|required|string|max:100',
                'adresse_complete' => 'sometimes|required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $employee->update($request->only([
                'prenom', 'nom', 'genre', 'etat_civil', 'region', 
                'ville', 'quartier', 'adresse_complete'
            ]));

            $employee->load(['nationality', 'activeContrat.employer']);
            $employee->photo_url = $this->getEmployeePhotoUrl($employee->id);

            return response()->json([
                'success' => true,
                'message' => 'Informations de l\'employé mises à jour avec succès',
                'data' => $employee,
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
     * DELETE /api/v1/employees/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
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

            // Terminer le contrat au lieu de supprimer l'employé
            $activeContract = $employee->activeContrat;
            if ($activeContract) {
                $activeContract->update([
                    'est_actif' => false,
                    'date_fin' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contrat de l\'employé terminé avec succès',
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
     * Sauvegarder un document en base64
     */
    private function saveBase64Document($employeeId, $type, $base64Data)
    {
        // Extraire les données base64
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $base64 = $matches[2];
        } else {
            $base64 = $base64Data;
            $mimeType = $type === 'photo' ? 'image/jpeg' : 'application/pdf';
        }

        // Décoder le base64
        $fileData = base64_decode($base64);
        
        // Générer un nom de fichier unique
        $extension = $type === 'photo' ? '.jpg' : '.pdf';
        $fileName = $type . '_' . $employeeId . '_' . time() . $extension;
        $filePath = "employees/{$type}s/" . $fileName;

        // Sauvegarder le fichier
        Storage::disk('public')->put($filePath, $fileData);

        // Enregistrer en base de données
        DocumentEmployee::create([
            'employee_id' => $employeeId,
            'type_document' => $type,
            'nom_fichier' => $fileName,
            'chemin_fichier' => $filePath,
            'mime_type' => $mimeType,
            'taille_fichier' => strlen($fileData),
            'extension' => ltrim($extension, '.'),
        ]);

        return $filePath;
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

    /**
     * Obtenir l'URL d'un document d'employé
     */
    private function getEmployeeDocumentUrl($employeeId, $type)
    {
        $document = DocumentEmployee::where('employee_id', $employeeId)
            ->where('type_document', $type)
            ->latest()
            ->first();

        return $document ? Storage::url($document->chemin_fichier) : null;
    }

    /**
     * Vérifier si une confirmation mensuelle est nécessaire
     */
    private function needsMonthlyConfirmation($contract)
    {
        if (!$contract || !$contract->est_actif) {
            return false;
        }

        $currentMonth = now()->month;
        $currentYear = now()->year;

        return !DB::table('confirmations_mensuelles')
            ->where('contrat_id', $contract->id)
            ->where('mois', $currentMonth)
            ->where('annee', $currentYear)
            ->exists();
    }
}