<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    public const CATEGORIES = [
        'Documents d\'analyse',
        'Compte rendu',
        'Convention de stage',
        'Autre',
    ];

    public const TYPES_RAPPORT = [
        'journalier' => 'Rapport journalier',
        'hebdomadaire' => 'Rapport hebdomadaire',
    ];

    protected $fillable = [
        'utilisateur_id',
        'project_id',
        'titre',
        'categorie',
        'type_rapport',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function derniereVersion(): ?DocumentVersion
    {
        return $this->versions()->orderByDesc('numero_version')->first();
    }

    public function libelleTypeRapport(): ?string
    {
        return self::TYPES_RAPPORT[$this->type_rapport] ?? null;
    }
}
