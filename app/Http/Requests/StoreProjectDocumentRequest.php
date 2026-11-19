<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectDocumentRequest extends FormRequest
{
    /** Seul le chef du groupe affecté au projet peut déposer (contrôle côté serveur). */
    public function authorize(): bool
    {
        return $this->user()->can('deposerDocument', $this->route('project'));
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'categorie' => ['required', Rule::in(Document::CATEGORIES)],
            'type_rapport' => ['required', Rule::in(array_keys(Document::TYPES_RAPPORT))],
            'fichier' => ['required', 'file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le nom du document est obligatoire.',
            'categorie.required' => 'Sélectionnez la catégorie du document.',
            'type_rapport.required' => 'Sélectionnez le type de rapport.',
            'fichier.mimes' => 'Le fichier doit être au format PDF, DOC, DOCX, PNG ou JPG.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
        ];
    }
}
