<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'stagiaire_id' => ['required', 'exists:users,id'],
            'mentor_id' => ['required', 'exists:users,id', 'different:stagiaire_id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'mode_travail' => ['required', 'in:presentiel,hybride,teletravail'],
        ];
    }
}
