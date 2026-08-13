<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = Project::findOrFail($this->input('project_id'));

        return $this->user()->can('update', $project);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priorite' => ['required', 'in:basse,moyenne,haute'],
            'date_echeance' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
