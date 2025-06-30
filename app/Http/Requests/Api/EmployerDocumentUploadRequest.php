<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\DocumentEmployer;

class EmployerDocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:5120', // 5MB max
            ],
            'type_document' => [
                'required',
                'string',
                'in:' . implode(',', array_keys(DocumentEmployer::getTypesDocuments())),
            ],
            'nom_fichier' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Le fichier est requis pour l\'employeur.',
            'document.file' => 'Le fichier doit être un fichier valide.',
            'document.mimes' => 'Le fichier doit être au format: JPEG, JPG, PNG ou PDF.',
            'document.max' => 'Le fichier ne doit pas dépasser 5MB.',
            'type_document.required' => 'Le type de document employeur est requis.',
            'type_document.in' => 'Type de document employeur invalide. Types acceptés: ' . 
                                  implode(', ', array_keys(DocumentEmployer::getTypesDocuments())),
            'nom_fichier.string' => 'Le nom du fichier doit être une chaîne de caractères.',
            'nom_fichier.max' => 'Le nom du fichier ne doit pas dépasser 255 caractères.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreurs de validation pour le document employeur',
            'errors' => $validator->errors(),
            'context' => 'employer_document_upload',
        ], 422));
    }

    /**
     * Préparer les données pour la validation
     */
    protected function prepareForValidation()
    {
        // Nettoyer le nom du fichier si fourni
        if ($this->has('nom_fichier')) {
            $this->merge([
                'nom_fichier' => trim($this->nom_fichier),
            ]);
        }

        // Normaliser le type de document
        if ($this->has('type_document')) {
            $this->merge([
                'type_document' => strtolower(trim($this->type_document)),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'document' => 'document employeur',
            'type_document' => 'type de document employeur',
            'nom_fichier' => 'nom du fichier',
        ];
    }
}