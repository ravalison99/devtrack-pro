<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Repositories\Contracts\StageRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function __construct(protected StageRepositoryInterface $stages) {}

    public function authorize(): bool
    {
        $stage = $this->stages->findById((int) $this->input('stage_id'));

        return $stage !== null && $this->user()->can('create', [Project::class, $stage]);
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
