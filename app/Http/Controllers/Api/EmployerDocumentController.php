<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\DocumentEmployer;
use App\Http\Requests\Api\EmployerDocumentUploadRequest;

class EmployerDocumentController extends Controller
{
    /**
     * Liste des documents de l'employeur connecté
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $documents = DocumentEmployer::where('employer_id', $employer->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'type_document' => $document->type_document,
                        'type_label' => $document->type_label,
                        'nom_fichier' => $document->nom_fichier,
                        'taille_fichier' => $document->taille_fichier,
                        'taille_fichier_formatee' => $document->taille_fichier_formatee,
                        'extension' => $document->extension,
                        'mime_type' => $document->mime_type,
                        'url' => $document->url,
                        'uploaded_at' => $document->created_at->format('Y-m-d H:i:s'),
                        'uploaded_at_human' => $document->created_at->diffForHumans(),
                        'is_image' => $document->isImage(),
                        'is_pdf' => $document->isPdf(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Documents employeur récupérés avec succès',
                'data' => [
                    'documents' => $documents,
                    'total' => $documents->count(),
                    'types_available' => DocumentEmployer::getTypesDocuments(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents employeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload d'un document employeur
     */
    public function upload(EmployerDocumentUploadRequest $request): JsonResponse
    {
        try {
            $employer = $request->user();
            $file = $request->file('document');
            $typeDocument = $request->input('type_document');

            // Vérifier si un document du même type existe déjà
            $existingDocument = DocumentEmployer::where('employer_id', $employer->id)
                ->where('type_document', $typeDocument)
                ->first();

            // Générer le nom de fichier unique
            $originalName = $request->input('nom_fichier') ?: $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = $this->generateUniqueFileName($typeDocument, $extension);

            // Créer le répertoire si nécessaire
            $directory = "documents/employers/{$employer->id}";
            
            // Stocker le fichier
            $filePath = $file->storeAs($directory, $fileName, 'public');

            if (!$filePath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload du fichier employeur',
                ], 500);
            }

            // Si un document du même type existe, supprimer l'ancien
            if ($existingDocument) {
                $this->deleteOldDocument($existingDocument);
            }

            // Créer l'enregistrement en base
            $document = DocumentEmployer::create([
                'employer_id' => $employer->id,
                'type_document' => $typeDocument,
                'nom_fichier' => $originalName,
                'chemin_fichier' => $filePath,
                'mime_type' => $file->getMimeType(),
                'taille_fichier' => $file->getSize(),
                'extension' => $extension,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document employeur uploadé avec succès',
                'data' => [
                    'document' => [
                        'id' => $document->id,
                        'type_document' => $document->type_document,
                        'type_label' => $document->type_label,
                        'nom_fichier' => $document->nom_fichier,
                        'taille_fichier' => $document->taille_fichier,
                        'taille_fichier_formatee' => $document->taille_fichier_formatee,
                        'extension' => $document->extension,
                        'mime_type' => $document->mime_type,
                        'url' => $document->url,
                        'uploaded_at' => $document->created_at->format('Y-m-d H:i:s'),
                        'is_image' => $document->isImage(),
                        'is_pdf' => $document->isPdf(),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document employeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un document employeur spécifique
     */
    public function show(Request $request, int $documentId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $document = DocumentEmployer::where('employer_id', $employer->id)
                ->where('id', $documentId)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document employeur non trouvé',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document employeur récupéré avec succès',
                'data' => [
                    'document' => [
                        'id' => $document->id,
                        'type_document' => $document->type_document,
                        'type_label' => $document->type_label,
                        'nom_fichier' => $document->nom_fichier,
                        'taille_fichier' => $document->taille_fichier,
                        'taille_fichier_formatee' => $document->taille_fichier_formatee,
                        'extension' => $document->extension,
                        'mime_type' => $document->mime_type,
                        'url' => $document->url,
                        'uploaded_at' => $document->created_at->format('Y-m-d H:i:s'),
                        'uploaded_at_human' => $document->created_at->diffForHumans(),
                        'is_image' => $document->isImage(),
                        'is_pdf' => $document->isPdf(),
                        'exists' => $document->exists(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du document employeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un document employeur
     */
    public function destroy(Request $request, int $documentId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $document = DocumentEmployer::where('employer_id', $employer->id)
                ->where('id', $documentId)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document employeur non trouvé',
                ], 404);
            }

            $this->deleteOldDocument($document);

            return response()->json([
                'success' => true,
                'message' => 'Document employeur supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du document employeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un document employeur par type
     */
    public function getByType(Request $request, string $type): JsonResponse
    {
        try {
            $employer = $request->user();
            
            // Valider le type
            if (!array_key_exists($type, DocumentEmployer::getTypesDocuments())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de document employeur invalide',
                    'available_types' => array_keys(DocumentEmployer::getTypesDocuments()),
                ], 422);
            }

            $document = DocumentEmployer::where('employer_id', $employer->id)
                ->where('type_document', $type)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucun document employeur de ce type',
                    'data' => [
                        'document' => null,
                        'type_requested' => $type,
                        'type_label' => DocumentEmployer::getTypesDocuments()[$type] ?? $type,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document employeur récupéré avec succès',
                'data' => [
                    'document' => [
                        'id' => $document->id,
                        'type_document' => $document->type_document,
                        'type_label' => $document->type_label,
                        'nom_fichier' => $document->nom_fichier,
                        'taille_fichier' => $document->taille_fichier,
                        'taille_fichier_formatee' => $document->taille_fichier_formatee,
                        'extension' => $document->extension,
                        'mime_type' => $document->mime_type,
                        'url' => $document->url,
                        'uploaded_at' => $document->created_at->format('Y-m-d H:i:s'),
                        'uploaded_at_human' => $document->created_at->diffForHumans(),
                        'is_image' => $document->isImage(),
                        'is_pdf' => $document->isPdf(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du document employeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des documents employeur
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $documents = DocumentEmployer::where('employer_id', $employer->id)->get();
            
            $stats = [
                'total_documents' => $documents->count(),
                'total_size_bytes' => $documents->sum('taille_fichier'),
                'total_size_formatted' => $this->formatBytes($documents->sum('taille_fichier')),
                'by_type' => [],
                'by_extension' => [],
                'last_upload' => null,
            ];

            // Statistiques par type
            foreach (DocumentEmployer::getTypesDocuments() as $type => $label) {
                $typeDocuments = $documents->where('type_document', $type);
                $stats['by_type'][$type] = [
                    'label' => $label,
                    'count' => $typeDocuments->count(),
                    'size_bytes' => $typeDocuments->sum('taille_fichier'),
                    'size_formatted' => $this->formatBytes($typeDocuments->sum('taille_fichier')),
                    'has_document' => $typeDocuments->count() > 0,
                ];
            }

            // Statistiques par extension
            $extensions = $documents->groupBy('extension');
            foreach ($extensions as $ext => $docs) {
                $stats['by_extension'][$ext] = [
                    'count' => $docs->count(),
                    'size_bytes' => $docs->sum('taille_fichier'),
                    'size_formatted' => $this->formatBytes($docs->sum('taille_fichier')),
                ];
            }

            // Dernier upload
            $lastDocument = $documents->sortByDesc('created_at')->first();
            if ($lastDocument) {
                $stats['last_upload'] = [
                    'date' => $lastDocument->created_at->format('Y-m-d H:i:s'),
                    'date_human' => $lastDocument->created_at->diffForHumans(),
                    'type' => $lastDocument->type_document,
                    'type_label' => $lastDocument->type_label,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des documents employeur récupérées',
                'data' => [
                    'stats' => $stats,
                    'employer_id' => $employer->id,
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
     * Générer un nom de fichier unique pour l'employeur
     */
    private function generateUniqueFileName(string $type, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(6);
        return "employer_{$type}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Supprimer un ancien document et son fichier
     */
    private function deleteOldDocument(DocumentEmployer $document): void
    {
        // Supprimer le fichier physique
        if (Storage::disk('public')->exists($document->chemin_fichier)) {
            Storage::disk('public')->delete($document->chemin_fichier);
        }
        
        // Supprimer l'enregistrement
        $document->delete();
    }

    /**
     * Formater la taille en bytes en format lisible
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
    }
}