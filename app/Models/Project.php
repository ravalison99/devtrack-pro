<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'stage_id',
        'nom',
        'description',
        'archive',
    ];

    protected function casts(): array
    {
        return [
            'archive' => 'boolean',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
