<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('stage'));
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', 'in:planifie,en_cours,termine,annule'],
        ];
    }
}
