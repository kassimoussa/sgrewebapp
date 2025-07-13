<?php
/**
 * Script de test pour vérifier les nouveaux endpoints
 * Exécuter depuis l'environnement Laravel avec PHP
 */

echo "=== TEST DES NOUVEAUX ENDPOINTS API ===\n\n";

// Base URL de l'API
$baseUrl = 'http://197.241.32.130:82/api/v1';

// Endpoints à tester
$endpoints = [
    // Nouveaux endpoints contrats
    'GET /contracts' => [
        'method' => 'GET',
        'url' => '/contracts',
        'description' => 'Liste des contrats',
        'auth_required' => true
    ],
    'PUT /contracts/{id}' => [
        'method' => 'PUT', 
        'url' => '/contracts/1',
        'description' => 'Modifier un contrat',
        'auth_required' => true,
        'data' => ['salaire_mensuel' => 150000]
    ],
    
    // Endpoints nationalités
    'GET /nationalities (public)' => [
        'method' => 'GET',
        'url' => '/nationalities',
        'description' => 'Liste publique des nationalités',
        'auth_required' => false
    ],
    'POST /nationalities' => [
        'method' => 'POST',
        'url' => '/nationalities',
        'description' => 'Créer une nationalité',
        'auth_required' => true,
        'data' => ['nom' => 'Test Nationalité', 'code' => 'TST']
    ],
    'GET /nationalities/statistics' => [
        'method' => 'GET',
        'url' => '/nationalities/statistics',
        'description' => 'Statistiques des nationalités',
        'auth_required' => true
    ],
    
    // Health check
    'GET /health' => [
        'method' => 'GET',
        'url' => '/health',
        'description' => 'Vérification santé API',
        'auth_required' => false
    ]
];

echo "Pour tester les endpoints, utilisez les commandes cURL suivantes:\n\n";

foreach ($endpoints as $name => $config) {
    $url = $baseUrl . $config['url'];
    $curlCmd = "curl -X {$config['method']} \\\n";
    $curlCmd .= "  '$url' \\\n";
    $curlCmd .= "  -H 'Content-Type: application/json'";
    
    if ($config['auth_required']) {
        $curlCmd .= " \\\n  -H 'Authorization: Bearer YOUR_TOKEN'";
    }
    
    if (isset($config['data'])) {
        $curlCmd .= " \\\n  -d '" . json_encode($config['data']) . "'";
    }
    
    echo "# $name\n";
    echo $curlCmd . "\n\n";
}

// Vérification routes Laravel (si accessible)
echo "=== VÉRIFICATION ROUTES LARAVEL ===\n";
echo "Exécutez ces commandes depuis le répertoire Laravel:\n\n";
echo "php artisan route:list --path=api/v1/contracts\n";
echo "php artisan route:list --path=api/v1/nationalities\n";
echo "php artisan route:cache\n";
echo "php artisan config:cache\n";