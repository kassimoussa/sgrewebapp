<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class DocumentEmployer extends Model
{
    use HasFactory;

    protected $table = 'documents_employers';

    protected $fillable = [
        'employer_id',
        'type_document',
        'nom_fichier',
        'chemin_fichier',
        'mime_type',
        'taille_fichier',
        'extension',
    ];

    protected $casts = [
        'taille_fichier' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    // Accessors
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->chemin_fichier) {
                    return null;
                }
                
                // Vérifier si le fichier existe
                if (!Storage::disk('public')->exists($this->chemin_fichier)) {
                    return null;
                }
                
                return Storage::disk('public')->url($this->chemin_fichier);
            },
        );
    }

    protected function tailleFichierFormatee(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->taille_fichier) return 'Inconnue';
                
                $bytes = $this->taille_fichier;
                $units = ['B', 'KB', 'MB', 'GB'];
                
                for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                    $bytes /= 1024;
                }
                
                return round($bytes, 2) . ' ' . $units[$i];
            },
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type_document) {
                'piece_identite' => 'Pièce d\'identité',
                'justificatif_domicile' => 'Justificatif de domicile',
                'autre' => 'Autre document',
                default => 'Document',
            },
        );
    }

    // Methods
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function exists(): bool
    {
        if (!$this->chemin_fichier) {
            return false;
        }
        
        return Storage::disk('public')->exists($this->chemin_fichier);
    }

    public function delete(): bool
    {
        // Supprimer le fichier physique avant de supprimer l'enregistrement
        if ($this->exists()) {
            Storage::disk('public')->delete($this->chemin_fichier);
        }
        
        return parent::delete();
    }

    /**
     * Obtenir l'URL de téléchargement sécurisée
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        // Retourner l'URL temporaire pour les fichiers sensibles
        return Storage::disk('public')->temporaryUrl(
            $this->chemin_fichier,
            now()->addHours(1)
        );
    }

    /**
     * Vérifier si le fichier est valide
     */
    public function isValid(): bool
    {
        return $this->exists() && 
               $this->taille_fichier > 0 && 
               !empty($this->mime_type);
    }

    /**
     * Obtenir la taille en MB
     */
    public function getTailleMB(): float
    {
        return round(($this->taille_fichier ?? 0) / (1024 * 1024), 2);
    }

    /**
     * Scope pour filtrer par type de document
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type_document', $type);
    }

    /**
     * Scope pour les documents valides (qui existent physiquement)
     */
    public function scopeValid($query)
    {
        return $query->whereNotNull('chemin_fichier')
                    ->where('taille_fichier', '>', 0);
    }

    /**
     * Obtenir tous les types de documents possibles
     */
    public static function getTypesDocuments(): array
    {
        return [
            'piece_identite' => 'Pièce d\'identité',
            'justificatif_domicile' => 'Justificatif de domicile',
            'autre' => 'Autre document',
        ];
    }

    /**
     * Obtenir les extensions autorisées
     */
    public static function getAllowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'pdf'];
    }

    /**
     * Obtenir les types MIME autorisés
     */
    public static function getAllowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'application/pdf',
        ];
    }

    /**
     * Vérifier si une extension est autorisée
     */
    public static function isAllowedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::getAllowedExtensions());
    }

    /**
     * Vérifier si un type MIME est autorisé
     */
    public static function isAllowedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::getAllowedMimeTypes());
    }
}