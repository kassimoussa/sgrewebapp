<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployerLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifiant' => 'required|string', // Email ou téléphone
            'mot_de_passe' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'identifiant.required' => 'L\'email ou le numéro de téléphone est requis.',
            'mot_de_passe.required' => 'Le mot de passe est requis.',
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
}