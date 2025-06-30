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

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'prenom',
        'nom',
        'genre',
        'etat_civil',
        'date_naissance',
        'nationality_id',
        'date_arrivee',
        'region',
        'ville',
        'quartier',
        'adresse_complete',
        'is_active',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_arrivee' => 'date',
        'is_active' => 'boolean',
    ];

    // Relations
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class);
    }

    public function activeContrat(): HasOne
    {
        return $this->hasOne(Contrat::class)->where('est_actif', true);
    }

    public function currentEmployer(): BelongsTo
    {
        return $this->belongsTo(Employer::class, 'employer_id')
                    ->through('activeContrat');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentEmployee::class);
    }

    public function photo(): HasOne
    {
        return $this->hasOne(DocumentEmployee::class)->where('type_document', 'photo');
    }

    public function identityDocument(): HasOne
    {
        return $this->hasOne(DocumentEmployee::class)->where('type_document', 'piece_identite');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(ConfirmationMensuelle::class)
                    ->through('contrats');
    }

    // Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeByNationality(Builder $query, int $nationalityId): void
    {
        $query->where('nationality_id', $nationalityId);
    }

    public function scopeByRegion(Builder $query, string $region): void
    {
        $query->where('region', $region);
    }

    public function scopeByAge(Builder $query, int $minAge, int $maxAge = null): void
    {
        $maxDate = Carbon::now()->subYears($minAge)->format('Y-m-d');
        $query->where('date_naissance', '<=', $maxDate);
        
        if ($maxAge) {
            $minDate = Carbon::now()->subYears($maxAge + 1)->format('Y-m-d');
            $query->where('date_naissance', '>', $minDate);
        }
    }

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('nom', 'like', "%{$search}%")
              ->orWhere('prenom', 'like', "%{$search}%")
              ->orWhereHas('nationality', function ($nq) use ($search) {
                  $nq->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Accessors
    protected function nomComplet(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->prenom} {$this->nom}",
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_naissance->age,
        );
    }

    protected function dureeEnDjibouti(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_arrivee->diffInYears(now()),
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo?->chemin_fichier 
                ? asset('storage/' . $this->photo->chemin_fichier) 
                : asset('images/default-employee.png'),
        );
    }

    // Methods
    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }

    public function hasIdentityDocument(): bool
    {
        return $this->identityDocument !== null;
    }

    public function hasActiveContract(): bool
    {
        return $this->activeContrat !== null;
    }

    public function getCurrentSalary(): ?float
    {
        return $this->activeContrat?->salaire_mensuel;
    }

    public function getCurrentEmploymentType(): ?string
    {
        return $this->activeContrat?->type_emploi;
    }
}
