<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class DocumentEmployee extends Model
{
    use HasFactory;

    
    protected $table = 'documents_employees';

    protected $fillable = [
        'employee_id',
        'type_document',
        'nom_fichier',
        'chemin_fichier',
        'mime_type',
        'taille_fichier',
        'extension',
    ];

    protected $casts = [
        'taille_fichier' => 'integer',
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Accessors (similaires à DocumentEmployer)
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::url($this->chemin_fichier),
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
                'photo' => 'Photo',
                'certificat_medical' => 'Certificat médical',
                'autre' => 'Autre document',
                default => 'Document',
            },
        );
    }

    // Methods (similaires à DocumentEmployer)
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
        return Storage::exists($this->chemin_fichier);
    }

    public function delete(): bool
    {
        if ($this->exists()) {
            Storage::delete($this->chemin_fichier);
        }
        
        return parent::delete();
    }
}