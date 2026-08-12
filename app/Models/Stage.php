<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $fillable = [
        'stagiaire_id',
        'mentor_id',
        'date_debut',
        'date_fin',
        'statut',
        'mode_travail',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
