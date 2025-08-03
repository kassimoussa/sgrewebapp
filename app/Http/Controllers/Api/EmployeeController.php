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
use App\Services\AttestationService;

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
                $employee->makeHidden(['photo_url', 'age']); // Masquer les accessors
                $employee->setAttribute('photo_url', $this->getEmployeePhotoUrl($employee->id));
                $employee->setAttribute('age', Carbon::parse($employee->date_naissance)->age);
                $employee->setAttribute('needs_confirmation', $this->needsMonthlyConfirmation($employee->activeContrat));
                $employee->setAttribute('document_status', $employee->getDocumentStatus());
                $employee->setAttribute('has_passport', $employee->hasPassport());
                $employee->setAttribute('needs_attestation', $employee->needsIdentityAttestation());
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
            
            // Contrat
            'type_emploi' => 'required|in:Ménage,Gardien,Jardinier,Coulis,Vendeur',
            'salaire_mensuel' => 'required|numeric|min:10000|max:500000',
            'date_debut' => 'required|date|before_or_equal:today',
            
            // Documents (base64 ou fichiers)
            'photo' => 'string', // Base64 de l'image
            'piece_identite' => 'string', // Base64 du document
            'passeport' => 'string', // Base64 du passeport
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

            if ($request->passeport) {
                $this->saveBase64Document($employee->id, 'passeport', $request->passeport);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $employee->load(['nationality', 'activeContrat.employer']);
            $employee->makeHidden(['photo_url']);
            $employee->setAttribute('photo_url', $this->getEmployeePhotoUrl($employee->id));

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
     * Générer une attestation d'identité pour un employé
     * POST /api/v1/employees/{id}/generate-attestation
     */
    public function generateAttestation(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $employee = Employee::with(['nationality', 'activeContrat.employer'])
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

            // Vérifier si l'employé a déjà un passeport
            if ($employee->hasPassport()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé possède déjà un passeport. Une attestation n\'est pas nécessaire.',
                ], 400);
            }

            $attestationService = new AttestationService();

            // Vérifier si une attestation valide existe déjà
            if ($attestationService->hasValidAttestation($employee)) {
                $attestationUrl = $attestationService->getValidAttestationUrl($employee);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Une attestation valide existe déjà',
                    'data' => [
                        'employee_id' => $employee->id,
                        'attestation_url' => $attestationUrl,
                        'status' => 'existing',
                    ],
                ]);
            }

            // Générer une nouvelle attestation
            $attestationPath = $attestationService->generateIdentityAttestation($employee);
            $attestationUrl = Storage::url($attestationPath);

            return response()->json([
                'success' => true,
                'message' => 'Attestation d\'identité générée avec succès',
                'data' => [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->nom_complet,
                    'attestation_url' => $attestationUrl,
                    'status' => 'generated',
                    'validity_period' => now()->addYear()->format('d/m/Y'),
                    'warning' => 'Cette attestation est valable 1 an. L\'employé doit obtenir un passeport avant expiration.',
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'attestation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut de l'attestation d'un employé
     * GET /api/v1/employees/{id}/attestation-status
     */
    public function getAttestationStatus(Request $request, $id): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $employee = Employee::with(['nationality', 'activeContrat.employer'])
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

            $attestationService = new AttestationService();
            
            $status = [
                'employee_id' => $employee->id,
                'has_passport' => $employee->hasPassport(),
                'has_identity_document' => $employee->hasIdentityDocument(),
                'document_status' => $employee->getDocumentStatus(),
                'needs_attestation' => $employee->needsIdentityAttestation(),
                'has_valid_attestation' => $attestationService->hasValidAttestation($employee),
                'attestation_url' => $attestationService->getValidAttestationUrl($employee),
            ];

            // Ajouter des recommandations
            if ($employee->hasPassport()) {
                $status['recommendation'] = 'Employé avec passeport - Éligible pour permis renouvelable';
            } elseif ($attestationService->hasValidAttestation($employee)) {
                $status['recommendation'] = 'Attestation valide - Employé doit obtenir un passeport avant expiration';
            } elseif ($employee->hasIdentityDocument()) {
                $status['recommendation'] = 'Pièce d\'identité disponible - Peut générer une attestation';
            } else {
                $status['recommendation'] = 'Aucun document - Doit fournir une pièce d\'identité avant génération d\'attestation';
            }

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut',
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
            $employee->makeHidden(['photo_url', 'age']); // Masquer les accessors
            $employee->setAttribute('photo_url', $this->getEmployeePhotoUrl($employee->id));
            $employee->setAttribute('identity_document_url', $this->getEmployeeDocumentUrl($employee->id, 'piece_identite'));
            $employee->setAttribute('passport_url', $this->getEmployeeDocumentUrl($employee->id, 'passeport'));
            $employee->setAttribute('age', Carbon::parse($employee->date_naissance)->age);
            $employee->setAttribute('needs_confirmation', $this->needsMonthlyConfirmation($employee->activeContrat));
            $employee->setAttribute('document_status', $employee->getDocumentStatus());
            $employee->setAttribute('has_passport', $employee->hasPassport());
            $employee->setAttribute('needs_attestation', $employee->needsIdentityAttestation());

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
            $employee->makeHidden(['photo_url']);
            $employee->setAttribute('photo_url', $this->getEmployeePhotoUrl($employee->id));

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

    /**
     * Uploader le passeport d'un employé
     * POST /api/v1/employees/{id}/passport
     */
    public function uploadPassport(Request $request, $id): JsonResponse
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
                'passeport' => 'required|string',
            ], [
                'passeport.required' => 'Le document passeport est requis.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Supprimer l'ancien passeport s'il existe
            $oldPassport = DocumentEmployee::where('employee_id', $employee->id)
                ->where('type_document', 'passeport')
                ->latest()
                ->first();

            if ($oldPassport) {
                Storage::disk('public')->delete($oldPassport->chemin_fichier);
                $oldPassport->delete();
            }

            // Sauvegarder le nouveau passeport
            $this->saveBase64Document($employee->id, 'passeport', $request->passeport);

            // Récupérer la nouvelle URL
            $passportUrl = $this->getEmployeeDocumentUrl($employee->id, 'passeport');

            return response()->json([
                'success' => true,
                'message' => 'Passeport ajouté avec succès',
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'passport_url' => $passportUrl,
                        'document_status' => $employee->getDocumentStatus(),
                        'has_passport' => true,
                        'needs_attestation' => false,
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du passeport',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour uniquement la photo d'un employé
     * PUT /api/v1/employees/{id}/photo
     */
    public function updatePhoto(Request $request, $id): JsonResponse
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
                'photo' => 'required|string',
            ], [
                'photo.required' => 'La photo est requise.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Supprimer l'ancienne photo si elle existe
            $oldPhoto = DocumentEmployee::where('employee_id', $employee->id)
                ->where('type_document', 'photo')
                ->latest()
                ->first();

            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto->chemin_fichier);
                $oldPhoto->delete();
            }

            // Sauvegarder la nouvelle photo
            $this->saveBase64Document($employee->id, 'photo', $request->photo);

            // Récupérer la nouvelle URL
            $photoUrl = $this->getEmployeePhotoUrl($employee->id);

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'photo_url' => $photoUrl,
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechercher un employé existant pour réenregistrement
     * POST /api/v1/employees/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'sometimes|string|max:10',
                'prenom' => 'sometimes|string|max:100',
                'nom' => 'sometimes|string|max:100',
                'telephone' => 'sometimes|string|max:20',
                'date_naissance' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $query = Employee::with(['nationality', 'activeContrat.employer']);

            // Recherche par ID employé (si un format spécifique existe)
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $employeeId = ltrim($request->employee_id, '0'); // Enlever les zéros de début
                $query->where('id', $employeeId);
            }

            // Recherche par prénom
            if ($request->has('prenom') && !empty($request->prenom)) {
                $query->where('prenom', 'like', '%' . $request->prenom . '%');
            }

            // Recherche par nom
            if ($request->has('nom') && !empty($request->nom)) {
                $query->where('nom', 'like', '%' . $request->nom . '%');
            }

            // Recherche par téléphone
            if ($request->has('telephone') && !empty($request->telephone)) {
                $cleanPhone = preg_replace('/[^0-9+]/', '', $request->telephone);
                $query->where(function($q) use ($request, $cleanPhone) {
                    $q->where('telephone', $request->telephone)
                      ->orWhere('telephone', $cleanPhone)
                      ->orWhere('telephone', 'like', '%' . substr($cleanPhone, -8));
                });
            }

            // Recherche par date de naissance
            if ($request->has('date_naissance') && !empty($request->date_naissance)) {
                $query->where('date_naissance', $request->date_naissance);
            }

            $employees = $query->limit(10)->get();

            // Enrichir les données
            $employees->transform(function ($employee) use ($request) {
                $currentEmployer = $employee->activeContrat ? $employee->activeContrat->employer : null;
                $canRegister = !$employee->activeContrat || !$employee->activeContrat->est_actif;

                return [
                    'id' => $employee->id,
                    'prenom' => $employee->prenom,
                    'nom' => $employee->nom,
                    'date_naissance' => $employee->date_naissance,
                    'telephone' => $employee->telephone,
                    'nationalite' => $employee->nationality ? $employee->nationality->nom : null,
                    'current_employer' => $currentEmployer ? ($currentEmployer->prenom . ' ' . $currentEmployer->nom) : 'Aucun employeur actuel',
                    'can_register' => $canRegister,
                    'photo_url' => $this->getEmployeePhotoUrl($employee->id),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Recherche effectuée avec succès',
                'data' => [
                    'employees' => $employees,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistrer un employé existant avec un nouvel employeur
     * POST /api/v1/employees/{id}/register-existing
     */
    public function registerExisting(Request $request, $id): JsonResponse
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

            // Vérifier si l'employé a déjà un contrat actif
            $activeContract = $employee->activeContrat;
            if ($activeContract && $activeContract->est_actif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé a déjà un contrat actif avec un autre employeur',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'contract.type_emploi' => 'required|in:Ménage,Gardien,Jardinier,Coulis,Vendeur',
                'contract.salaire_mensuel' => 'required|numeric|min:10000|max:500000',
                'contract.date_debut' => 'required|date|after_or_equal:today',
            ], [
                'contract.type_emploi.required' => 'Le type d\'emploi est requis.',
                'contract.salaire_mensuel.required' => 'Le salaire mensuel est requis.',
                'contract.salaire_mensuel.min' => 'Le salaire doit être d\'au moins 10 000 FDJ.',
                'contract.date_debut.required' => 'La date de début est requise.',
                'contract.date_debut.after_or_equal' => 'La date de début doit être aujourd\'hui ou dans le futur.',
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
                'date_debut' => $request->contract['date_debut'],
                'type_emploi' => $request->contract['type_emploi'],
                'salaire_mensuel' => $request->contract['salaire_mensuel'],
                'est_actif' => true,
            ]);

            // Activer l'employé
            $employee->update(['is_active' => true]);

            DB::commit();

            // Charger les relations pour la réponse
            $employee->load(['nationality', 'activeContrat.employer']);
            $employee->makeHidden(['photo_url']);
            $employee->setAttribute('photo_url', $this->getEmployeePhotoUrl($employee->id));

            return response()->json([
                'success' => true,
                'message' => 'Employé enregistré avec succès',
                'data' => [
                    'employee' => $employee,
                    'contract' => $contract,
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
}