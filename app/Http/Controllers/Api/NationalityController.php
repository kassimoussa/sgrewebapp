<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NationalityController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/v1/nationalities
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Nationality::query();

            // Recherche
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('nom', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            }

            // Tri
            $sortBy = $request->get('sort_by', 'nom');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortBy, $sortDirection);

            // Option pour récupérer toutes les nationalités sans pagination
            if ($request->get('all', false)) {
                $nationalities = $query->get();
            } else {
                // Pagination
                $perPage = $request->get('per_page', 50);
                $nationalities = $query->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'message' => 'Liste des nationalités récupérée avec succès',
                'data' => $nationalities,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des nationalités',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/v1/nationalities
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:100|unique:nationalities,nom',
            'code' => 'required|string|max:3|unique:nationalities,code',
        ], [
            'nom.required' => 'Le nom de la nationalité est requis.',
            'nom.unique' => 'Cette nationalité existe déjà.',
            'code.required' => 'Le code de la nationalité est requis.',
            'code.unique' => 'Ce code de nationalité existe déjà.',
            'code.max' => 'Le code ne doit pas dépasser 3 caractères.',
        ]);

        try {
            $nationality = Nationality::create([
                'nom' => $request->nom,
                'code' => strtoupper($request->code),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nationalité créée avec succès',
                'data' => $nationality,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la nationalité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/v1/nationalities/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $nationality = Nationality::withCount(['employees', 'activeEmployees'])->find($id);

            if (!$nationality) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nationalité non trouvée',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nationalité récupérée avec succès',
                'data' => $nationality,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la nationalité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/v1/nationalities/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $nationality = Nationality::find($id);

            if (!$nationality) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nationalité non trouvée',
                ], 404);
            }

            $request->validate([
                'nom' => 'sometimes|required|string|max:100|unique:nationalities,nom,' . $id,
                'code' => 'sometimes|required|string|max:3|unique:nationalities,code,' . $id,
            ], [
                'nom.unique' => 'Cette nationalité existe déjà.',
                'code.unique' => 'Ce code de nationalité existe déjà.',
                'code.max' => 'Le code ne doit pas dépasser 3 caractères.',
            ]);

            $updateData = [];
            if ($request->has('nom')) {
                $updateData['nom'] = $request->nom;
            }
            if ($request->has('code')) {
                $updateData['code'] = strtoupper($request->code);
            }

            $nationality->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Nationalité mise à jour avec succès',
                'data' => $nationality,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la nationalité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/v1/nationalities/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $nationality = Nationality::withCount('employees')->find($id);

            if (!$nationality) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nationalité non trouvée',
                ], 404);
            }

            // Vérifier s'il y a des employés avec cette nationalité
            if ($nationality->employees_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette nationalité car elle est utilisée par ' . $nationality->employees_count . ' employé(s)',
                ], 409);
            }

            $nationality->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nationalité supprimée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la nationalité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get nationalities with employee statistics
     * GET /api/v1/nationalities/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $nationalities = Nationality::withCount([
                'employees',
                'activeEmployees'
            ])->get();

            $stats = [
                'total_nationalities' => $nationalities->count(),
                'total_employees' => $nationalities->sum('employees_count'),
                'total_active_employees' => $nationalities->sum('active_employees_count'),
                'nationalities' => $nationalities,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des nationalités récupérées avec succès',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}