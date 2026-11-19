<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /** Toute personne autorisée à voir la tâche peut y laisser un message. */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenu.required' => 'Le commentaire ne peut pas être vide.',
            'contenu.max' => 'Le commentaire ne doit pas dépasser 2000 caractères.',
        ];
    }
}
