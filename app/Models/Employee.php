<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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

    public function currentEmployer()
    {
        return $this->activeContrat?->employer;
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

    public function confirmations(): HasManyThrough
    {
        return $this->hasManyThrough(ConfirmationMensuelle::class, Contrat::class);
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
                  $nq->where('nom', 'like', "%{$search}%");
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

    protected function photoThumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->photo?->chemin_fichier) {
                    return asset('images/default-employee.png');
                }

                $originalPath = $this->photo->chemin_fichier;
                $pathInfo = pathinfo($originalPath);
                $thumbnailPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
                
                if (file_exists(storage_path('app/public/' . $thumbnailPath))) {
                    return asset('storage/' . $thumbnailPath);
                }
                
                // Si le thumbnail n'existe pas, générer et retourner l'original en attendant
                $this->generateThumbnail();
                return asset('storage/' . $originalPath);
            }
        );
    }

    public function generateThumbnail(): bool
    {
        if (!$this->photo?->chemin_fichier) {
            return false;
        }

        $originalPath = storage_path('app/public/' . $this->photo->chemin_fichier);
        if (!file_exists($originalPath)) {
            return false;
        }

        $pathInfo = pathinfo($this->photo->chemin_fichier);
        $thumbDir = storage_path('app/public/' . $pathInfo['dirname'] . '/thumbs');
        $thumbnailPath = $thumbDir . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];

        // Créer le dossier thumbs s'il n'existe pas
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        // Générer le thumbnail avec GD
        try {
            $imageType = exif_imagetype($originalPath);
            
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($originalPath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($originalPath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($originalPath);
                    break;
                default:
                    return false;
            }

            if (!$source) return false;

            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            
            // Créer un thumbnail carré de 60x60 (optimisé pour l'affichage)
            $thumbSize = 60;
            $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
            
            // Préserver la transparence pour PNG
            if ($imageType === IMAGETYPE_PNG) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefill($thumb, 0, 0, $transparent);
            }

            // Redimensionner en gardant les proportions et centrer
            $ratio = min($thumbSize / $sourceWidth, $thumbSize / $sourceHeight);
            $newWidth = intval($sourceWidth * $ratio);
            $newHeight = intval($sourceHeight * $ratio);
            
            $x = intval(($thumbSize - $newWidth) / 2);
            $y = intval(($thumbSize - $newHeight) / 2);

            imagecopyresampled($thumb, $source, $x, $y, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

            // Sauvegarder
            $result = false;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($thumb, $thumbnailPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    $result = imagepng($thumb, $thumbnailPath, 6);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($thumb, $thumbnailPath);
                    break;
            }

            imagedestroy($source);
            imagedestroy($thumb);

            return $result;
        } catch (\Exception $e) {
            return false;
        }
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
