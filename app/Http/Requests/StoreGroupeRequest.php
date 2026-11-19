<?php

namespace App\Http\Requests;

use App\Models\Groupe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Groupe::class);
    }

    protected function prepareForValidation(): void
    {
        // Les listes déroulantes vides (membres facultatifs) ne comptent pas comme des membres.
        $membres = $this->input('membres', []);

        $this->merge([
            'membres' => is_array($membres) ? array_values(array_filter($membres, fn ($id) => $id !== null && $id !== '')) : $membres,
        ]);
    }

    public function rules(): array
    {
        $membres = is_array($this->input('membres')) ? $this->input('membres') : [];

        return [
            'nom' => ['required', 'string', 'max:255'],
            'membres' => ['required', 'array', 'min:1', 'max:'.Groupe::MAX_MEMBRES],
            'membres.*' => [
                'integer',
                'distinct',
                // Uniquement des stagiaires actifs déjà enregistrés...
                Rule::exists('users', 'id')->where('role', 'stagiaire')->where('is_active', true),
                // ...qui n'appartiennent à aucun autre groupe.
                Rule::unique('groupe_user', 'user_id'),
            ],
            'chef_id' => ['required', 'integer', Rule::in($membres)],
            // Projet du mentor connecté, pas encore affecté à un groupe.
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('mentor_id', $this->user()->id),
                Rule::unique('groupes', 'project_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du groupe est obligatoire.',
            'membres.required' => 'Sélectionnez au moins un stagiaire.',
            'membres.min' => 'Sélectionnez au moins un stagiaire.',
            'membres.max' => 'Un groupe ne peut pas contenir plus de '.Groupe::MAX_MEMBRES.' stagiaires.',
            'membres.*.distinct' => 'Un même stagiaire ne peut pas être sélectionné deux fois.',
            'membres.*.exists' => 'Un des stagiaires sélectionnés est introuvable ou inactif.',
            'membres.*.unique' => 'Un des stagiaires sélectionnés appartient déjà à un groupe.',
            'chef_id.required' => 'Désignez un chef de groupe.',
            'chef_id.in' => 'Le chef de groupe doit être l\'un des membres du groupe.',
            'project_id.required' => 'Sélectionnez un projet.',
            'project_id.exists' => 'Le projet sélectionné est introuvable.',
            'project_id.unique' => 'Ce projet est déjà affecté à un groupe.',
        ];
    }
}
