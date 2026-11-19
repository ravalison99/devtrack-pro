<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Groupe extends Model
{
    public const MAX_MEMBRES = 3;

    protected $fillable = [
        'nom',
        'mentor_id',
        'chef_id',
        'project_id',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'groupe_user', 'groupe_id', 'user_id')->withTimestamps();
    }
}
