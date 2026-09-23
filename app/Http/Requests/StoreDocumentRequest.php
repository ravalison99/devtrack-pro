<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function __construct(protected ProjectRepositoryInterface $projects) {}

    public function authorize(): bool
    {
        if (! $this->user()->can('create', Document::class)) {
            return false;
        }

        if ($this->filled('project_id')) {
            $project = $this->projects->findById((int) $this->input('project_id'));

            return $project !== null && $this->user()->can('update', $project);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'categorie' => ['nullable', Rule::in(Document::CATEGORIES)],
            'fichier' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx,png,jpg,jpeg'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ];
    }
}
