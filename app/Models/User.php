<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMentor(): bool
    {
        return $this->role === 'mentor';
    }

    public function isStagiaire(): bool
    {
        return $this->role === 'stagiaire';
    }

    public function stagesEnTantQueStagiaire(): HasMany
    {
        return $this->hasMany(Stage::class, 'stagiaire_id');
    }

    public function stagesEnTantQueMentor(): HasMany
    {
        return $this->hasMany(Stage::class, 'mentor_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'stagiaire_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'utilisateur_id');
    }

    public function groupes(): BelongsToMany
    {
        return $this->belongsToMany(Groupe::class, 'groupe_user', 'user_id', 'groupe_id')->withTimestamps();
    }

    public function groupesDiriges(): HasMany
    {
        return $this->hasMany(Groupe::class, 'chef_id');
    }

    public function estChefDeGroupe(): bool
    {
        return $this->groupesDiriges()->exists();
    }
}
