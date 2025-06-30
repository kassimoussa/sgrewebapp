<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmployerRegisterRequest;
use App\Http\Requests\Api\EmployerLoginRequest;
use App\Models\Employer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel employeur (étape 1 - informations essentielles)
     */
    public function register(EmployerRegisterRequest $request): JsonResponse
    {
        try {
            // Créer l'employeur avec les informations minimales
            $employer = Employer::create([
                'email' => $request->email,
                'telephone' => $request->telephone,
                'mot_de_passe_hash' => Hash::make($request->mot_de_passe),
                'is_active' => true,
                // Les autres champs restent null pour l'instant
                'prenom' => null,
                'nom' => null,
                'genre' => null,
                'region' => null,
                'ville' => null,
                'quartier' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créer le token d'authentification
            $token = $employer->createToken('mobile-app', ['employer'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'employer' => [
                        'id' => $employer->id,
                        'email' => $employer->email,
                        'telephone' => $employer->telephone,
                        'is_active' => $employer->is_active,
                        'profile_completed' => false, // Indique que le profil n'est pas complet
                        'created_at' => $employer->created_at->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'next_step' => 'complete_profile', // Indique la prochaine étape
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion d'un employeur
     */
    public function login(EmployerLoginRequest $request): JsonResponse
    {
        try {
            $identifiant = $request->identifiant;
            
            // Déterminer si c'est un email ou un téléphone
            $isEmail = filter_var($identifiant, FILTER_VALIDATE_EMAIL);
            
            // Rechercher l'employeur par email ou téléphone
            if ($isEmail) {
                $employer = Employer::where('email', $identifiant)->first();
            } else {
                // Nettoyer le numéro de téléphone (enlever espaces, tirets, etc.)
                $cleanPhone = preg_replace('/[^0-9+]/', '', $identifiant);
                
                $employer = Employer::where(function($query) use ($identifiant, $cleanPhone) {
                    $query->where('telephone', $identifiant)
                          ->orWhere('telephone', $cleanPhone)
                          // Chercher aussi sans le préfixe +253
                          ->orWhere('telephone', 'like', '%' . substr($cleanPhone, -8))
                          // Chercher avec différents formats
                          ->orWhere('telephone', 'like', '%' . substr($identifiant, -8));
                })->first();
            }

            // Vérifier si l'employeur existe et le mot de passe est correct
            if (!$employer || !Hash::check($request->mot_de_passe, $employer->mot_de_passe_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant ou mot de passe incorrect',
                ], 401);
            }

            // Vérifier si le compte est actif
            if (!$employer->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé. Contactez l\'administration.',
                ], 403);
            }

            // Révoquer les anciens tokens (optionnel - pour forcer une seule session)
            $employer->tokens()->delete();

            // Créer un nouveau token
            $token = $employer->createToken('mobile-app', ['employer'])->plainTextToken;

            // Mettre à jour la date de dernière connexion
            $employer->update(['updated_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'employer' => [
                        'id' => $employer->id,
                        'prenom' => $employer->prenom,
                        'nom' => $employer->nom,
                        'email' => $employer->email,
                        'telephone' => $employer->telephone,
                        'genre' => $employer->genre,
                        'region' => $employer->region,
                        'ville' => $employer->ville,
                        'quartier' => $employer->quartier,
                        'is_active' => $employer->is_active,
                        'created_at' => $employer->created_at->toISOString(),
                        'updated_at' => $employer->updated_at->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion (suppression du token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Supprimer le token actuel
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations de l'employeur connecté
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            // Vérifier si le profil est complet
            $profileCompleted = !is_null($employer->prenom) && 
                               !is_null($employer->nom) && 
                               !is_null($employer->genre) && 
                               !is_null($employer->region) && 
                               !is_null($employer->ville) && 
                               !is_null($employer->quartier);

            return response()->json([
                'success' => true,
                'data' => [
                    'employer' => [
                        'id' => $employer->id,
                        'prenom' => $employer->prenom,
                        'nom' => $employer->nom,
                        'email' => $employer->email,
                        'telephone' => $employer->telephone,
                        'genre' => $employer->genre,
                        'region' => $employer->region,
                        'ville' => $employer->ville,
                        'quartier' => $employer->quartier,
                        'is_active' => $employer->is_active,
                        'profile_completed' => $profileCompleted,
                        'created_at' => $employer->created_at->toISOString(),
                        'updated_at' => $employer->updated_at->toISOString(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compléter le profil de l'employeur (étape 2)
     */
    public function completeProfile(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            $validatedData = $request->validate([
                'prenom' => 'required|string|max:100',
                'nom' => 'required|string|max:100',
                'genre' => 'required|in:Homme,Femme',
                'region' => 'required|string|in:Djibouti,Ali Sabieh,Dikhil,Tadjourah,Obock,Arta',
                'ville' => 'required|string|max:100',
                'quartier' => 'required|string|max:100',
            ], [
                'prenom.required' => 'Le prénom est requis.',
                'nom.required' => 'Le nom est requis.',
                'genre.required' => 'Le genre est requis.',
                'genre.in' => 'Le genre doit être "Homme" ou "Femme".',
                'region.required' => 'La région est requise.',
                'region.in' => 'La région sélectionnée n\'est pas valide.',
                'ville.required' => 'La ville est requise.',
                'quartier.required' => 'Le quartier est requis.',
            ]);

            $employer->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profil complété avec succès',
                'data' => [
                    'employer' => [
                        'id' => $employer->id,
                        'prenom' => $employer->prenom,
                        'nom' => $employer->nom,
                        'email' => $employer->email,
                        'telephone' => $employer->telephone,
                        'genre' => $employer->genre,
                        'region' => $employer->region,
                        'ville' => $employer->ville,
                        'quartier' => $employer->quartier,
                        'is_active' => $employer->is_active,
                        'profile_completed' => true,
                        'created_at' => $employer->created_at->toISOString(),
                        'updated_at' => $employer->updated_at->toISOString(),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la completion du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil de l'employeur
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            $validatedData = $request->validate([
                'prenom' => 'sometimes|string|max:100',
                'nom' => 'sometimes|string|max:100',
                'genre' => 'sometimes|in:Homme,Femme',
                'telephone' => 'sometimes|string|unique:employers,telephone,' . $employer->id . '|regex:/^[0-9+\-\s]+$/|max:20',
                'region' => 'sometimes|string|in:Djibouti,Ali Sabieh,Dikhil,Tadjourah,Obock,Arta',
                'ville' => 'sometimes|string|max:100',
                'quartier' => 'sometimes|string|max:100',
            ], [
                'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
                'telephone.regex' => 'Le format du numéro de téléphone est invalide.',
                'genre.in' => 'Le genre doit être "Homme" ou "Femme".',
                'region.in' => 'La région sélectionnée n\'est pas valide.',
            ]);

            $employer->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'employer' => [
                        'id' => $employer->id,
                        'prenom' => $employer->prenom,
                        'nom' => $employer->nom,
                        'email' => $employer->email,
                        'telephone' => $employer->telephone,
                        'genre' => $employer->genre,
                        'region' => $employer->region,
                        'ville' => $employer->ville,
                        'quartier' => $employer->quartier,
                        'is_active' => $employer->is_active,
                        'created_at' => $employer->created_at->toISOString(),
                        'updated_at' => $employer->updated_at->toISOString(),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si un identifiant (email ou téléphone) existe
     */
    public function checkIdentifier(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'identifiant' => 'required|string'
            ], [
                'identifiant.required' => 'L\'identifiant est requis.'
            ]);

            $identifiant = $validatedData['identifiant'];
            
            // Déterminer si c'est un email ou un téléphone
            $isEmail = filter_var($identifiant, FILTER_VALIDATE_EMAIL);
            
            $exists = false;
            $type = null;
            
            if ($isEmail) {
                $exists = Employer::where('email', $identifiant)->exists();
                $type = 'email';
            } else {
                // Nettoyer le numéro de téléphone
                $cleanPhone = preg_replace('/[^0-9+]/', '', $identifiant);
                
                $exists = Employer::where(function($query) use ($identifiant, $cleanPhone) {
                    $query->where('telephone', $identifiant)
                          ->orWhere('telephone', $cleanPhone)
                          ->orWhere('telephone', 'like', '%' . substr($cleanPhone, -8))
                          ->orWhere('telephone', 'like', '%' . substr($identifiant, -8));
                })->exists();
                $type = 'telephone';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => $exists,
                    'type' => $type,
                    'identifiant' => $identifiant
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            $validatedData = $request->validate([
                'ancien_mot_de_passe' => 'required|string',
                'nouveau_mot_de_passe' => 'required|string|min:6|confirmed',
                'nouveau_mot_de_passe_confirmation' => 'required|string',
            ], [
                'ancien_mot_de_passe.required' => 'L\'ancien mot de passe est requis.',
                'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est requis.',
                'nouveau_mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.',
                'nouveau_mot_de_passe.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
            ]);

            // Vérifier l'ancien mot de passe
            if (!Hash::check($validatedData['ancien_mot_de_passe'], $employer->mot_de_passe_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'ancien mot de passe est incorrect.',
                ], 400);
            }

            // Mettre à jour le mot de passe
            $employer->update([
                'mot_de_passe_hash' => Hash::make($validatedData['nouveau_mot_de_passe'])
            ]);

            // Révoquer tous les tokens existants pour forcer une reconnexion
            $employer->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès. Veuillez vous reconnecter.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}