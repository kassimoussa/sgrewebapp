<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentEmployee;
use App\Models\DocumentEmployer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * Upload a document for an employee.
     * POST /api/v1/documents/employee/{employee_id}
     */
    public function uploadEmployeeDocument(Request $request, $employeeId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type_document' => 'required|in:photo,piece_identite,certificat_medical,autre',
            'document' => 'required|string', // Base64 encoded
            'nom_fichier' => 'sometimes|string|max:255',
        ], [
            'type_document.required' => 'Le type de document est requis.',
            'type_document.in' => 'Le type de document doit être: photo, piece_identite, certificat_medical ou autre.',
            'document.required' => 'Le document est requis.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $employer = $request->user();
            
            // Vérifier que l'employé appartient à l'employeur connecté
            $employee = Employee::whereHas('contrats', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->find($employeeId);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            // Supprimer l'ancien document du même type s'il existe
            $existingDocument = DocumentEmployee::where('employee_id', $employeeId)
                ->where('type_document', $request->type_document)
                ->first();

            if ($existingDocument && Storage::disk('public')->exists($existingDocument->chemin_fichier)) {
                Storage::disk('public')->delete($existingDocument->chemin_fichier);
                $existingDocument->delete();
            }

            // Sauvegarder le nouveau document
            $documentPath = $this->saveBase64Document(
                $request->document, 
                $request->type_document, 
                'employees', 
                $employeeId
            );

            $document = DocumentEmployee::create([
                'employee_id' => $employeeId,
                'type_document' => $request->type_document,
                'nom_fichier' => $request->get('nom_fichier', $this->generateFileName($request->type_document)),
                'chemin_fichier' => $documentPath['path'],
                'mime_type' => $documentPath['mime_type'],
                'taille_fichier' => $documentPath['size'],
                'extension' => $documentPath['extension'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => [
                    'document' => $document,
                    'url' => Storage::url($document->chemin_fichier),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a document for an employer.
     * POST /api/v1/documents/employer
     */
    public function uploadEmployerDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type_document' => 'required|in:piece_identite,justificatif_domicile,autre',
            'document' => 'required|string', // Base64 encoded
            'nom_fichier' => 'sometimes|string|max:255',
        ], [
            'type_document.required' => 'Le type de document est requis.',
            'type_document.in' => 'Le type de document doit être: piece_identite, justificatif_domicile ou autre.',
            'document.required' => 'Le document est requis.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $employer = $request->user();

            // Supprimer l'ancien document du même type s'il existe
            $existingDocument = DocumentEmployer::where('employer_id', $employer->id)
                ->where('type_document', $request->type_document)
                ->first();

            if ($existingDocument && Storage::disk('public')->exists($existingDocument->chemin_fichier)) {
                Storage::disk('public')->delete($existingDocument->chemin_fichier);
                $existingDocument->delete();
            }

            // Sauvegarder le nouveau document
            $documentPath = $this->saveBase64Document(
                $request->document, 
                $request->type_document, 
                'employers', 
                $employer->id
            );

            $document = DocumentEmployer::create([
                'employer_id' => $employer->id,
                'type_document' => $request->type_document,
                'nom_fichier' => $request->get('nom_fichier', $this->generateFileName($request->type_document)),
                'chemin_fichier' => $documentPath['path'],
                'mime_type' => $documentPath['mime_type'],
                'taille_fichier' => $documentPath['size'],
                'extension' => $documentPath['extension'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => [
                    'document' => $document,
                    'url' => Storage::url($document->chemin_fichier),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get documents for an employee.
     * GET /api/v1/documents/employee/{employee_id}
     */
    public function getEmployeeDocuments(Request $request, $employeeId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            // Vérifier que l'employé appartient à l'employeur connecté
            $employee = Employee::whereHas('contrats', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->find($employeeId);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            $documents = DocumentEmployee::where('employee_id', $employeeId)
                ->orderBy('type_document')
                ->orderBy('created_at', 'desc')
                ->get();

            // Ajouter les URLs et vérifier l'existence aux documents
            $documents->transform(function ($document) {
                $document->url = Storage::url($document->chemin_fichier);
                $document->exists = Storage::disk('public')->exists($document->chemin_fichier);
                $document->taille_fichier_formatee = $this->formatFileSize($document->taille_fichier);
                $document->type_label = $this->getTypeLabel($document->type_document);
                return $document;
            });

            return response()->json([
                'success' => true,
                'message' => 'Documents de l\'employé récupérés avec succès',
                'data' => [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->prenom . ' ' . $employee->nom,
                    'documents' => $documents,
                    'total_documents' => $documents->count(),
                    'total_size' => $documents->sum('taille_fichier'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get documents for the authenticated employer.
     * GET /api/v1/documents/employer
     */
    public function getEmployerDocuments(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $documents = DocumentEmployer::where('employer_id', $employer->id)
                ->orderBy('type_document')
                ->orderBy('created_at', 'desc')
                ->get();

            // Ajouter les URLs et vérifier l'existence aux documents
            $documents->transform(function ($document) {
                $document->url = Storage::url($document->chemin_fichier);
                $document->exists = Storage::disk('public')->exists($document->chemin_fichier);
                $document->taille_fichier_formatee = $this->formatFileSize($document->taille_fichier);
                $document->type_label = $this->getTypeLabel($document->type_document);
                return $document;
            });

            return response()->json([
                'success' => true,
                'message' => 'Documents de l\'employeur récupérés avec succès',
                'data' => [
                    'employer_id' => $employer->id,
                    'employer_name' => $employer->prenom . ' ' . $employer->nom,
                    'documents' => $documents,
                    'total_documents' => $documents->count(),
                    'total_size' => $documents->sum('taille_fichier'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an employee document.
     * DELETE /api/v1/documents/employee/{document_id}
     */
    public function deleteEmployeeDocument(Request $request, $documentId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $document = DocumentEmployee::whereHas('employee.contrats', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->find($documentId);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($document->chemin_fichier)) {
                Storage::disk('public')->delete($document->chemin_fichier);
            }

            // Supprimer l'enregistrement en base
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an employer document.
     * DELETE /api/v1/documents/employer/{document_id}
     */
    public function deleteEmployerDocument(Request $request, $documentId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            $document = DocumentEmployer::where('employer_id', $employer->id)
                ->find($documentId);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document non trouvé',
                ], 404);
            }

            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($document->chemin_fichier)) {
                Storage::disk('public')->delete($document->chemin_fichier);
            }

            // Supprimer l'enregistrement en base
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a document.
     * GET /api/v1/documents/download/{type}/{document_id}
     */
    public function downloadDocument(Request $request, $type, $documentId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            if ($type === 'employee') {
                $document = DocumentEmployee::whereHas('employee.contrats', function($q) use ($employer) {
                    $q->where('employer_id', $employer->id);
                })->find($documentId);
            } elseif ($type === 'employer') {
                $document = DocumentEmployer::where('employer_id', $employer->id)
                    ->find($documentId);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de document invalide. Utilisez "employee" ou "employer".',
                ], 400);
            }

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            if (!Storage::disk('public')->exists($document->chemin_fichier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé sur le serveur',
                ], 404);
            }

            $fileContent = Storage::disk('public')->get($document->chemin_fichier);
            $base64Content = base64_encode($fileContent);

            return response()->json([
                'success' => true,
                'message' => 'Document récupéré avec succès',
                'data' => [
                    'document' => $document,
                    'content' => $base64Content,
                    'mime_type' => $document->mime_type,
                    'filename' => $document->nom_fichier,
                    'size' => $document->taille_fichier,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if required documents are present for an employee.
     * GET /api/v1/documents/employee/{employee_id}/check
     */
    public function checkEmployeeDocuments(Request $request, $employeeId): JsonResponse
    {
        try {
            $employer = $request->user();
            
            // Vérifier que l'employé appartient à l'employeur connecté
            $employee = Employee::whereHas('contrats', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->find($employeeId);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé ou vous n\'avez pas l\'autorisation',
                ], 404);
            }

            $documents = DocumentEmployee::where('employee_id', $employeeId)->get();
            $documentTypes = $documents->pluck('type_document')->toArray();

            $requiredDocuments = ['photo', 'piece_identite'];
            $missingDocuments = array_diff($requiredDocuments, $documentTypes);

            $check = [
                'employee_id' => $employeeId,
                'employee_name' => $employee->prenom . ' ' . $employee->nom,
                'has_photo' => in_array('photo', $documentTypes),
                'has_identity' => in_array('piece_identite', $documentTypes),
                'has_medical_certificate' => in_array('certificat_medical', $documentTypes),
                'is_complete' => empty($missingDocuments),
                'missing_documents' => array_map(function($type) {
                    return [
                        'type' => $type,
                        'label' => $this->getTypeLabel($type),
                    ];
                }, $missingDocuments),
                'present_documents' => $documents->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->type_document,
                        'label' => $this->getTypeLabel($doc->type_document),
                        'nom_fichier' => $doc->nom_fichier,
                        'taille' => $doc->taille_fichier,
                        'taille_formatee' => $this->formatFileSize($doc->taille_fichier),
                        'url' => Storage::url($doc->chemin_fichier),
                        'created_at' => $doc->created_at,
                    ];
                }),
                'total_documents' => $documents->count(),
                'total_size' => $documents->sum('taille_fichier'),
                'completion_percentage' => round((count($requiredDocuments) - count($missingDocuments)) / count($requiredDocuments) * 100),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Vérification des documents effectuée avec succès',
                'data' => $check,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get document statistics for the employer.
     * GET /api/v1/documents/statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $employer = $request->user();

            // Statistiques des documents employeur
            $employerDocs = DocumentEmployer::where('employer_id', $employer->id)->get();
            
            // Statistiques des documents employés
            $employeeDocs = DocumentEmployee::whereHas('employee.contrats', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->get();

            $stats = [
                'employer_documents' => [
                    'total' => $employerDocs->count(),
                    'by_type' => $employerDocs->groupBy('type_document')->map(function($docs, $type) {
                        return [
                            'type' => $type,
                            'label' => $this->getTypeLabel($type),
                            'count' => $docs->count(),
                            'total_size' => $docs->sum('taille_fichier'),
                        ];
                    })->values(),
                    'total_size' => $employerDocs->sum('taille_fichier'),
                    'total_size_formatted' => $this->formatFileSize($employerDocs->sum('taille_fichier')),
                ],
                'employee_documents' => [
                    'total' => $employeeDocs->count(),
                    'by_type' => $employeeDocs->groupBy('type_document')->map(function($docs, $type) {
                        return [
                            'type' => $type,
                            'label' => $this->getTypeLabel($type),
                            'count' => $docs->count(),
                            'total_size' => $docs->sum('taille_fichier'),
                        ];
                    })->values(),
                    'total_size' => $employeeDocs->sum('taille_fichier'),
                    'total_size_formatted' => $this->formatFileSize($employeeDocs->sum('taille_fichier')),
                ],
                'global' => [
                    'total_documents' => $employerDocs->count() + $employeeDocs->count(),
                    'total_size' => $employerDocs->sum('taille_fichier') + $employeeDocs->sum('taille_fichier'),
                    'total_size_formatted' => $this->formatFileSize($employerDocs->sum('taille_fichier') + $employeeDocs->sum('taille_fichier')),
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des documents récupérées avec succès',
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

    /**
     * Get document types available for each entity.
     * GET /api/v1/documents/types
     */
    public function getDocumentTypes(): JsonResponse
    {
        $documentTypes = [
            'employee' => [
                ['value' => 'photo', 'label' => 'Photo'],
                ['value' => 'piece_identite', 'label' => 'Pièce d\'identité'],
                ['value' => 'certificat_medical', 'label' => 'Certificat médical'],
                ['value' => 'autre', 'label' => 'Autre document'],
            ],
            'employer' => [
                ['value' => 'piece_identite', 'label' => 'Pièce d\'identité'],
                ['value' => 'justificatif_domicile', 'label' => 'Justificatif de domicile'],
                ['value' => 'autre', 'label' => 'Autre document'],
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Types de documents récupérés avec succès',
            'data' => $documentTypes,
        ]);
    }

    // ============================================
    // MÉTHODES PRIVÉES UTILITAIRES
    // ============================================

    /**
     * Sauvegarder un document en base64
     */
    private function saveBase64Document($base64Data, $type, $entityType, $entityId): array
    {
        // Extraire les données base64
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $base64 = $matches[2];
        } else {
            $base64 = $base64Data;
            $mimeType = $this->guessMimeType($type);
        }

        // Décoder le base64
        $fileData = base64_decode($base64);
        
        if ($fileData === false) {
            throw new \Exception('Format base64 invalide');
        }

        // Déterminer l'extension
        $extension = $this->getExtensionFromMimeType($mimeType);
        
        // Générer un nom de fichier unique
        $fileName = $type . '_' . $entityId . '_' . time() . '.' . $extension;
        $filePath = "{$entityType}/{$type}s/" . $fileName;

        // Sauvegarder le fichier
        Storage::disk('public')->put($filePath, $fileData);

        return [
            'path' => $filePath,
            'mime_type' => $mimeType,
            'size' => strlen($fileData),
            'extension' => $extension,
        ];
    }

    /**
     * Générer un nom de fichier
     */
    private function generateFileName($type): string
    {
        return $type . '_' . time() . '_' . uniqid();
    }

    /**
     * Deviner le type MIME selon le type de document
     */
    private function guessMimeType($type): string
    {
        $mimeTypes = [
            'photo' => 'image/jpeg',
            'piece_identite' => 'application/pdf',
            'certificat_medical' => 'application/pdf',
            'justificatif_domicile' => 'application/pdf',
            'autre' => 'application/pdf',
        ];

        return $mimeTypes[$type] ?? 'application/octet-stream';
    }

    /**
     * Obtenir l'extension à partir du type MIME
     */
    private function getExtensionFromMimeType($mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        return $extensions[$mimeType] ?? 'bin';
    }

    /**
     * Obtenir le libellé d'un type de document
     */
    private function getTypeLabel($type): string
    {
        $labels = [
            'photo' => 'Photo',
            'piece_identite' => 'Pièce d\'identité',
            'certificat_medical' => 'Certificat médical',
            'justificatif_domicile' => 'Justificatif de domicile',
            'autre' => 'Autre document',
        ];

        return $labels[$type] ?? ucfirst($type);
    }

    /**
     * Formater la taille d'un fichier
     */
    private function formatFileSize($bytes): string
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $size = $bytes / pow(1024, $power);
        $formattedSize = round($size, 2);

        return $formattedSize . ' ' . $units[$power];
    }
}