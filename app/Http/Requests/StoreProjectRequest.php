<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Type contrôlé sur le contenu réel du fichier (mimes), taille limitée à 5 Mo.
            'cahier_charges' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'cahier_charges.required' => 'Le cahier des charges est obligatoire.',
            'cahier_charges.mimes' => 'Le cahier des charges doit être un fichier PDF, DOC ou DOCX.',
            'cahier_charges.max' => 'Le cahier des charges ne doit pas dépasser 5 Mo.',
        ];
    }
}
