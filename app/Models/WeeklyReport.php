<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    protected $fillable = [
        'stagiaire_id',
        'semaine',
        'contenu',
        'fichier_pdf',
        'statut',
        'commentaire_mentor',
    ];

    protected function casts(): array
    {
        return [
            'semaine' => 'integer',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }
}
