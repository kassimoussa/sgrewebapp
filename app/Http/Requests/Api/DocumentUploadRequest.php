<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DocumentUploadRequest extends FormRequest
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
                'in:piece_identite,justificatif_domicile,autre',
            ],
            'nom_fichier' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Le fichier est requis.',
            'document.file' => 'Le fichier doit être un fichier valide.',
            'document.mimes' => 'Le fichier doit être au format: JPEG, JPG, PNG ou PDF.',
            'document.max' => 'Le fichier ne doit pas dépasser 5MB.',
            'type_document.required' => 'Le type de document est requis.',
            'type_document.in' => 'Type de document invalide. Types acceptés: piece_identite, justificatif_domicile, autre.',
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
            'message' => 'Erreurs de validation',
            'errors' => $validator->errors(),
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
    }
}