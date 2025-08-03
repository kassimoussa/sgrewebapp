<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Vérifier la structure de la table documents_employees
    $columns = Schema::getColumnListing('documents_employees');
    echo "Colonnes de la table documents_employees:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    // Vérifier le type enum actuel
    $result = DB::select("SHOW COLUMNS FROM documents_employees WHERE Field = 'type_document'");
    if (!empty($result)) {
        echo "\nType enum actuel pour type_document:\n";
        echo $result[0]->Type . "\n";
    }
    
    // Vérifier les types de documents existants
    $types = DB::table('documents_employees')
        ->select('type_document')
        ->distinct()
        ->pluck('type_document');
    
    echo "\nTypes de documents existants dans la base:\n";
    foreach ($types as $type) {
        echo "- $type\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}