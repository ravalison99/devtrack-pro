<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = Project::find($this->input('project_id'));

        return $project !== null && $this->user()->can('gererTaches', $project);
    }

    public function rules(): array
    {
        $project = Project::with(['stage', 'groupe.membres'])->find($this->input('project_id'));
        $stagiairesDuProjet = $project?->stagiaires()->pluck('id')->all() ?? [];

        return [
            'project_id' => ['required', 'exists:projects,id'],
            // Un projet confié à un groupe : la tâche doit être assignée à l'un de ses membres.
            'assigne_id' => [
                $project?->groupe ? 'required' : 'nullable',
                'integer',
                Rule::in($stagiairesDuProjet),
            ],
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priorite' => ['required', 'in:basse,moyenne,haute'],
            'date_echeance' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigne_id.required' => 'Sélectionnez le membre du groupe à qui confier la tâche.',
            'assigne_id.in' => 'La tâche doit être assignée à un membre du groupe du projet.',
        ];
    }
}
