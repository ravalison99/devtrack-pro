<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    protected $fillable = [
        'stagiaire_id',
        'date',
        'contenu',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }
}
