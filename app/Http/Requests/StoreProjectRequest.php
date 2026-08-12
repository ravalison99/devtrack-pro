<?php

namespace App\Http\Requests;

use App\Models\Stage;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stage = Stage::findOrFail($this->input('stage_id'));

        return $this->user()->can('create', [\App\Models\Project::class, $stage]);
    }

    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'exists:stages,id'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
