<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Employer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'prenom',
        'nom',
        'genre',
        'telephone',
        'region',
        'ville',
        'quartier',
        'email',
        'mot_de_passe_hash',
        'email_verified_at',
        'is_active',
    ];

    protected $hidden = [
        'mot_de_passe_hash',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the password attribute name for authentication
     * AJOUTER cette méthode
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }

    /**
     * Get the name of the unique identifier for the user.
     * AJOUTER cette méthode pour permettre la connexion par email OU téléphone
     */
    public function getAuthIdentifierName()
    {
        return 'email'; // Par défaut, mais on gère les deux dans le contrôleur
    }

    // Relations
    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class);
    }

    public function activeContrats(): HasMany
    {
        return $this->hasMany(Contrat::class)->where('est_actif', true);
    }

    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(Employee::class, Contrat::class);
    }

    public function activeEmployees(): HasManyThrough
    {
        return $this->hasManyThrough(Employee::class, Contrat::class)
                    ->where('contrats.est_actif', true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentEmployer::class);
    }

    // Alias pour compatibilité si nécessaire
    public function documentsEmployer(): HasMany
    {
        return $this->hasMany(DocumentEmployer::class);
    }

    public function identityDocuments(): HasMany
    {
        return $this->hasMany(DocumentEmployer::class)->where('type_document', 'piece_identite');
    }

    // Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeByRegion(Builder $query, string $region): void
    {
        $query->where('region', $region);
    }

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('nom', 'like', "%{$search}%")
              ->orWhere('prenom', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('telephone', 'like', "%{$search}%");
        });
    }

    // Accessors & Mutators
    protected function nomComplet(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->prenom} {$this->nom}",
        );
    }

    protected function adresseComplete(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->quartier}, {$this->ville}, {$this->region}",
        );
    }

    // Methods
    public function hasIdentityDocument(): bool
    {
        return $this->identityDocuments()->exists();
    }

    public function getActiveEmployeesCount(): int
    {
        return $this->activeEmployees()->count();
    }

    public function getTotalEmployeesCount(): int
    {
        return $this->employees()->count();
    }

    /**
     * Obtenir le nombre de contrats actifs
     */
    public function getActiveContractsCount(): int
    {
        return $this->activeContrats()->count();
    }

    /**
     * Obtenir le nombre total de contrats
     */
    public function getTotalContractsCount(): int
    {
        return $this->contrats()->count();
    }

    /**
     * Scope pour rechercher par identifiant (email ou téléphone)
     * AJOUTER ce scope
     */
    public function scopeByIdentifier(Builder $query, string $identifier): void
    {
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail) {
            $query->where('email', $identifier);
        } else {
            // Nettoyer le numéro de téléphone
            $cleanPhone = preg_replace('/[^0-9+]/', '', $identifier);
            
            $query->where(function($q) use ($identifier, $cleanPhone) {
                $q->where('telephone', $identifier)
                  ->orWhere('telephone', $cleanPhone)
                  ->orWhere('telephone', 'like', '%' . substr($cleanPhone, -8))
                  ->orWhere('telephone', 'like', '%' . substr($identifier, -8));
            });
        }
    }

    /**
     * Méthode pour formater le téléphone de manière cohérente
     * AJOUTER cette méthode
     */
    public function getFormattedPhoneAttribute(): string
    {
        // Vous pouvez personnaliser le format selon vos besoins
        $phone = preg_replace('/[^0-9+]/', '', $this->telephone);
        
        // Si c'est un numéro djiboutien à 8 chiffres, ajouter +253
        if (strlen($phone) === 8 && !str_starts_with($phone, '+')) {
            return '+253 ' . $phone;
        }
        
        return $this->telephone;
    }
}