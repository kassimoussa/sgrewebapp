<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ConfirmationMensuelle extends Model
{
    use HasFactory;

    protected $table = 'confirmations_mensuelles';

    protected $fillable = [
        'contrat_id',
        'mois',
        'annee',
        'statut_emploi',
        'jours_travailles',
        'jours_absence',
        'jours_conge',
        'salaire_verse',
        'observations',
        'date_confirmation',
    ];

    protected $casts = [
        'mois' => 'integer',
        'annee' => 'integer',
        'jours_travailles' => 'integer',
        'jours_absence' => 'integer',
        'jours_conge' => 'integer',
        'salaire_verse' => 'decimal:2',
        'date_confirmation' => 'datetime',
    ];

    // Relations
    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class);
    }

    // Méthodes pour accéder aux relations via contrat
    public function getEmployeeAttribute()
    {
        return $this->contrat?->employee;
    }

    public function getEmployerAttribute()
    {
        return $this->contrat?->employer;
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('statut_emploi', $status);
    }

    public function scopeByPeriod(Builder $query, int $month, int $year): void
    {
        $query->where('mois', $month)->where('annee', $year);
    }

    public function scopeByYear(Builder $query, int $year): void
    {
        $query->where('annee', $year);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('statut_emploi', 'actif');
    }

    // Accessors
    protected function nomMois(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ][$this->mois] ?? 'Inconnu',
        );
    }

    protected function statutLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->statut_emploi) {
                'actif' => 'Actif',
                'conge' => 'En congé',
                'absent' => 'Absent',
                'termine' => 'Terminé',
                default => 'Inconnu',
            },
        );
    }

    protected function periode(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->nom_mois} {$this->annee}",
        );
    }

    // Methods
    public function isActive(): bool
    {
        return $this->statut_emploi === 'actif';
    }

    public function calculateSalary(): float
    {
        $contrat = $this->contrat;
        if (!$contrat) return 0;

        $salaireJournalier = $contrat->salaire_mensuel / 26;
        return $salaireJournalier * $this->jours_travailles;
    }
}