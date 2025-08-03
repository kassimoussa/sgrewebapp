<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Modifier directement l'enum pour ajouter permis_travail
    $sql = "ALTER TABLE documents_employees MODIFY COLUMN type_document ENUM(
        'piece_identite', 
        'photo', 
        'certificat_medical', 
        'passeport',
        'attestation_identite',
        'permis_travail',
        'autre'
    )";
    
    DB::statement($sql);
    echo "✅ Type 'permis_travail' ajouté avec succès à l'enum type_document\n";
    
    // Vérifier le résultat
    $result = DB::select("SHOW COLUMNS FROM documents_employees WHERE Field = 'type_document'");
    if (!empty($result)) {
        echo "✅ Nouveau type enum:\n";
        echo $result[0]->Type . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}