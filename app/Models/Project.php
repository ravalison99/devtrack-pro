<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Project extends Model
{
    protected $fillable = [
        'stage_id',
        'mentor_id',
        'nom',
        'description',
        'cahier_charges_nom',
        'cahier_charges_chemin',
        'cahier_charges_taille',
        'archive',
    ];

    protected function casts(): array
    {
        return [
            'archive' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Projet rattaché à un stage (données historiques) : le mentor est celui du stage.
        static::creating(function (Project $project) {
            if ($project->mentor_id === null && $project->stage_id !== null) {
                $project->mentor_id = Stage::withTrashed()->whereKey($project->stage_id)->value('mentor_id');
            }
        });
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function groupe(): HasOne
    {
        return $this->hasOne(Groupe::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** Vrai lorsque le projet est associé à un groupe (valeur réelle issue de la base). */
    public function estAffecte(): bool
    {
        return $this->groupe !== null;
    }

    public function aUnCahierDesCharges(): bool
    {
        return $this->cahier_charges_chemin !== null;
    }

    /** Le mentor responsable : propriétaire du projet, à défaut mentor du stage historique. */
    public function mentorId(): ?int
    {
        return $this->mentor_id ?? $this->stage?->mentor_id;
    }

    /** Le chef de groupe répartit les tâches du projet confié à son groupe. */
    public function estChef(User $user): bool
    {
        return $this->groupe !== null && $this->groupe->chef_id === $user->id;
    }

    /** Stagiaire concerné : membre du groupe affecté ou stagiaire du stage historique. */
    public function aPourStagiaire(User $user): bool
    {
        if ($this->stage?->stagiaire_id === $user->id) {
            return true;
        }

        return $this->groupe?->membres->contains('id', $user->id) ?? false;
    }

    /**
     * Stagiaires concernés par le projet : membres du groupe affecté et stagiaire du stage historique.
     * Les comptes introuvables sont ignorés.
     *
     * @return Collection<int, User>
     */
    public function stagiaires(): Collection
    {
        return collect([$this->stage?->stagiaire])
            ->merge($this->groupe?->membres ?? [])
            ->filter()
            ->unique('id')
            ->values();
    }

    public function scopeDuMentor(Builder $query, int $mentorId): Builder
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeDuStagiaire(Builder $query, int $stagiaireId): Builder
    {
        return $query->where(function (Builder $q) use ($stagiaireId) {
            $q->whereHas('stage', fn (Builder $s) => $s->where('stagiaire_id', $stagiaireId))
                ->orWhereHas('groupe.membres', fn (Builder $m) => $m->where('users.id', $stagiaireId));
        });
    }

    public function scopeDuChef(Builder $query, int $chefId): Builder
    {
        return $query->whereHas('groupe', fn (Builder $g) => $g->where('chef_id', $chefId));
    }

    public function scopeNonAffecte(Builder $query): Builder
    {
        return $query->whereDoesntHave('groupe');
    }
}
