<?php

namespace App\Services;

use App\Models\Employee;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

class AttestationService
{
    /**
     * Générer une attestation d'identité PDF pour un employé
     */
    public function generateIdentityAttestation(Employee $employee): string
    {
        // Données pour le template
        $data = [
            'employee' => $employee,
            'employer' => $employee->activeContrat?->employer,
            'contract' => $employee->activeContrat,
            'generation_date' => Carbon::now(),
            'attestation_number' => $this->generateAttestationNumber($employee->id),
            'validity_period' => Carbon::now()->addYear(), // Valide 1 an
        ];

        // Générer le HTML depuis le template
        $html = View::make('pdf.attestation-identite', $data)->render();

        // Nom du fichier PDF
        $fileName = "attestation_identite_{$employee->id}_" . time() . ".pdf";
        $filePath = "employees/attestations/" . $fileName;
        $fullPath = storage_path('app/public/' . $filePath);

        // Créer le dossier s'il n'existe pas
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Générer le PDF avec Browsershot
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(20, 20, 20, 20)
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Configuration spécifique pour serveur Linux/Ubuntu
        if (PHP_OS_FAMILY === 'Linux') {
            // Essayer différents chemins Chrome/Chromium sur Linux
            $chromePaths = [
                '/usr/bin/google-chrome-stable',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium'
            ];
            
            foreach ($chromePaths as $path) {
                if (file_exists($path)) {
                    $browsershot->setChromePath($path)
                        ->setOption('args', [
                            '--no-sandbox',
                            '--disable-setuid-sandbox',
                            '--disable-dev-shm-usage',
                            '--disable-gpu',
                            '--no-first-run',
                            '--disable-background-timer-throttling',
                            '--disable-backgrounding-occluded-windows',
                            '--disable-renderer-backgrounding'
                        ]);
                    break;
                }
            }
        }

        $browsershot->save($fullPath);

        // Enregistrer le document en base
        $employee->documents()->create([
            'type_document' => 'attestation_identite',
            'nom_fichier' => $fileName,
            'chemin_fichier' => $filePath,
            'mime_type' => 'application/pdf',
            'taille_fichier' => filesize($fullPath),
            'extension' => 'pdf',
        ]);

        return $filePath;
    }

    /**
     * Générer un numéro unique d'attestation
     */
    private function generateAttestationNumber(int $employeeId): string
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->format('m');
        $employeeCode = str_pad($employeeId, 6, '0', STR_PAD_LEFT);
        
        return "ATT-{$year}{$month}-{$employeeCode}";
    }

    /**
     * Vérifier si une attestation existe et est valide
     */
    public function hasValidAttestation(Employee $employee): bool
    {
        $attestation = $employee->documents()
            ->where('type_document', 'attestation_identite')
            ->latest()
            ->first();

        if (!$attestation) {
            return false;
        }

        // Vérifier si le fichier existe toujours
        if (!Storage::disk('public')->exists($attestation->chemin_fichier)) {
            return false;
        }

        // Vérifier si l'attestation n'est pas trop ancienne (1 an max)
        $createdAt = $attestation->created_at;
        return $createdAt->diffInMonths(now()) < 12;
    }

    /**
     * Obtenir l'URL de l'attestation valide
     */
    public function getValidAttestationUrl(Employee $employee): ?string
    {
        if (!$this->hasValidAttestation($employee)) {
            return null;
        }

        $attestation = $employee->documents()
            ->where('type_document', 'attestation_identite')
            ->latest()
            ->first();

        return Storage::url($attestation->chemin_fichier);
    }

    /**
     * Générer une carte de permis de travail PDF pour un employé avec passeport
     */
    public function generateWorkPermitCard(Employee $employee): string
    {
        // Vérifier que l'employé a un passeport
        if (!$employee->hasPassport()) {
            throw new \Exception('L\'employé doit avoir un passeport valide pour générer un permis de travail.');
        }

        // Données pour le template
        $data = [
            'employee' => $employee,
            'employer' => $employee->activeContrat?->employer,
            'contract' => $employee->activeContrat,
            'generation_date' => Carbon::now(),
            'permit_number' => $this->generatePermitNumber($employee->id),
        ];

        // Générer le HTML depuis le template
        $html = View::make('pdf.carte-permis-travail', $data)->render();

        // Nom du fichier PDF
        $fileName = "carte_permis_travail_{$employee->id}_" . time() . ".pdf";
        $filePath = "employees/permits/" . $fileName;
        $fullPath = storage_path('app/public/' . $filePath);

        // Créer le dossier s'il n'existe pas
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Générer le PDF avec Browsershot
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Configuration spécifique pour serveur Linux/Ubuntu
        if (PHP_OS_FAMILY === 'Linux') {
            // Essayer différents chemins Chrome/Chromium sur Linux
            $chromePaths = [
                '/usr/bin/google-chrome-stable',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium'
            ];
            
            foreach ($chromePaths as $path) {
                if (file_exists($path)) {
                    $browsershot->setChromePath($path)
                        ->setOption('args', [
                            '--no-sandbox',
                            '--disable-setuid-sandbox',
                            '--disable-dev-shm-usage',
                            '--disable-gpu',
                            '--no-first-run',
                            '--disable-background-timer-throttling',
                            '--disable-backgrounding-occluded-windows',
                            '--disable-renderer-backgrounding'
                        ]);
                    break;
                }
            }
        }

        $browsershot->save($fullPath);

        // Enregistrer le document en base
        $employee->documents()->create([
            'type_document' => 'permis_travail',
            'nom_fichier' => $fileName,
            'chemin_fichier' => $filePath,
            'mime_type' => 'application/pdf',
            'taille_fichier' => filesize($fullPath),
            'extension' => 'pdf',
        ]);

        return $filePath;
    }

    /**
     * Générer un numéro unique de permis de travail
     */
    private function generatePermitNumber(int $employeeId): string
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->format('m');
        $employeeCode = str_pad($employeeId, 6, '0', STR_PAD_LEFT);
        
        return "PT-{$year}{$month}-{$employeeCode}";
    }

    /**
     * Vérifier si un permis de travail existe et est valide
     */
    public function hasValidWorkPermit(Employee $employee): bool
    {
        $permit = $employee->documents()
            ->where('type_document', 'permis_travail')
            ->latest()
            ->first();

        if (!$permit) {
            return false;
        }

        // Vérifier si le fichier existe toujours
        if (!Storage::disk('public')->exists($permit->chemin_fichier)) {
            return false;
        }

        // Vérifier que l'employé a toujours un passeport
        return $employee->hasPassport();
    }

    /**
     * Obtenir l'URL du permis de travail valide
     */
    public function getValidWorkPermitUrl(Employee $employee): ?string
    {
        if (!$this->hasValidWorkPermit($employee)) {
            return null;
        }

        $permit = $employee->documents()
            ->where('type_document', 'permis_travail')
            ->latest()
            ->first();

        return Storage::url($permit->chemin_fichier);
    }
}