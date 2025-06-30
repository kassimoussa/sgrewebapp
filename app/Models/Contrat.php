<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Contrat extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'employee_id',
        'date_debut',
        'date_fin',
        'type_emploi',
        'salaire_mensuel',
        'est_actif',
        'notes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'salaire_mensuel' => 'decimal:2',
        'est_actif' => 'boolean',
    ];

    // Relations
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(ConfirmationMensuelle::class);
    }

    public function latestConfirmation(): HasOne
    {
        return $this->hasOne(ConfirmationMensuelle::class)->latestOfMany();
    }

    // Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('est_actif', true);
    }

    public function scopeInactive(Builder $query): void
    {
        $query->where('est_actif', false);
    }

    public function scopeByEmployer(Builder $query, int $employerId): void
    {
        $query->where('employer_id', $employerId);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type_emploi', $type);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): void
    {
        $query->where('date_fin', '<=', Carbon::now()->addDays($days))
              ->where('date_fin', '>=', Carbon::now())
              ->where('est_actif', true);
    }

    // Accessors
    protected function duree(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fin = $this->date_fin ?? now();
                return $this->date_debut->diffInDays($fin);
            },
        );
    }

    protected function dureeEnMois(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fin = $this->date_fin ?? now();
                return $this->date_debut->diffInMonths($fin);
            },
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_fin && $this->date_fin->isPast(),
        );
    }

    protected function isExpiringSoon(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_fin && $this->date_fin->isBefore(now()->addDays(30)),
        );
    }

    // Methods
    public function terminate(Carbon $endDate = null): bool
    {
        return $this->update([
            'est_actif' => false,
            'date_fin' => $endDate ?? now(),
        ]);
    }

    public function needsConfirmation(int $month = null, int $year = null): bool
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        return !$this->confirmations()
                    ->where('mois', $month)
                    ->where('annee', $year)
                    ->exists();
    }

    public function getLastConfirmation(): ?ConfirmationMensuelle
    {
        return $this->confirmations()->latest('annee')->latest('mois')->first();
    }
}