<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentEmployee;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;

class DocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        
        foreach ($employees as $index => $employee) {
            // Créer le dossier pour cet employé
            $employeeFolder = "employees/employee_{$employee->id}";
            
            // 90% des employés ont une photo
            if (fake()->boolean(90)) {
                $this->createDocument($employee->id, 'photo', $employeeFolder, 'photo', 'jpg', 'image/jpeg');
            }
            
            // 70% des employés ont une pièce d'identité
            if (fake()->boolean(70)) {
                $this->createDocument($employee->id, 'piece_identite', $employeeFolder, 'piece_identite', 'pdf', 'application/pdf');
            }
            
            // 30% des employés ont un certificat médical
            if (fake()->boolean(30)) {
                $this->createDocument($employee->id, 'certificat_medical', $employeeFolder, 'certificat_medical', 'pdf', 'application/pdf');
            }
            
            // 20% des employés ont un passeport (pour tester notre système)
            if (fake()->boolean(20)) {
                $this->createDocument($employee->id, 'passeport', $employeeFolder, 'passeport', 'pdf', 'application/pdf');
            }
            
            // 10% des employés ont d'autres documents
            if (fake()->boolean(10)) {
                $this->createDocument($employee->id, 'autre', $employeeFolder, 'autre_document', 'pdf', 'application/pdf');
            }
        }
    }
    
    /**
     * Créer un document factice
     */
    private function createDocument($employeeId, $type, $folder, $baseName, $extension, $mimeType)
    {
        $fileName = $baseName . '_' . $employeeId . '_' . time() . '.' . $extension;
        $filePath = $folder . '/' . $fileName;
        
        // Créer un contenu factice selon le type
        if ($extension === 'jpg') {
            // Créer une image factice (1x1 pixel)
            $imageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
        } else {
            // Créer un PDF factice minimal
            $imageContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n174\n%%EOF";
        }
        
        // Sauvegarder le fichier factice
        Storage::disk('public')->put($filePath, $imageContent);
        
        // Créer l'enregistrement en base
        DocumentEmployee::create([
            'employee_id' => $employeeId,
            'type_document' => $type,
            'nom_fichier' => $fileName,
            'chemin_fichier' => $filePath,
            'mime_type' => $mimeType,
            'taille_fichier' => strlen($imageContent),
            'extension' => $extension,
        ]);
    }
}