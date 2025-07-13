<?php

/**
 * Script de test pour tous les nouveaux endpoints API SGRE
 * Usage: php test_api_endpoints.php
 */

class ApiTester
{
    private $baseUrl;
    private $token;
    private $results = [];

    public function __construct($baseUrl = 'http://localhost/sgreweb/public/api/v1')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function runAllTests()
    {
        echo "🚀 Début des tests API SGRE\n";
        echo "Base URL: {$this->baseUrl}\n\n";

        // 1. Test authentification et récupération du token
        $this->testAuth();

        if (!$this->token) {
            echo "❌ Impossible de continuer sans token d'authentification\n";
            return;
        }

        // 2. Tests des nouveaux endpoints
        $this->testRefreshToken();
        $this->testEmployeeSearch();
        $this->testEmployeePhotoUpdate();
        $this->testContractManagement();
        $this->testDashboardStats();
        $this->testMonthlyConfirmations();
        $this->testProfileDocuments();

        // 3. Résumé des résultats
        $this->showResults();
    }

    private function testAuth()
    {
        echo "📝 Test 1: Authentification\n";
        
        // Login avec données de test
        $loginData = [
            'identifiant' => 'test@example.com', // À adapter selon vos données
            'mot_de_passe' => 'password123'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->token = $response['data']['token'];
            $this->addResult('auth_login', true, 'Login réussi');
            echo "   ✅ Login réussi\n";
        } else {
            $this->addResult('auth_login', false, 'Échec du login');
            echo "   ❌ Échec du login\n";
            
            // Essayer avec des données par défaut
            echo "   🔄 Tentative avec données par défaut...\n";
            $defaultData = [
                'identifiant' => 'admin@admin.com',
                'mot_de_passe' => 'password'
            ];
            
            $response = $this->makeRequest('POST', '/auth/login', $defaultData);
            if ($response && isset($response['success']) && $response['success']) {
                $this->token = $response['data']['token'];
                $this->addResult('auth_login_default', true, 'Login avec données par défaut réussi');
                echo "   ✅ Login avec données par défaut réussi\n";
            }
        }
        echo "\n";
    }

    private function testRefreshToken()
    {
        echo "🔄 Test 2: Refresh Token\n";
        
        $response = $this->makeRequest('POST', '/auth/refresh', [
            'refresh_token' => $this->token
        ]);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->addResult('auth_refresh', true, 'Token refresh réussi');
            echo "   ✅ Token refresh réussi\n";
        } else {
            $this->addResult('auth_refresh', false, 'Échec du token refresh');
            echo "   ❌ Échec du token refresh\n";
        }
        echo "\n";
    }

    private function testEmployeeSearch()
    {
        echo "🔍 Test 3: Recherche d'employés\n";
        
        // Test recherche par prénom
        $searchData = [
            'prenom' => 'Marie'
        ];
        
        $response = $this->makeRequest('POST', '/employees/search', $searchData);
        
        if ($response && isset($response['success']) && $response['success']) {
            $count = count($response['data']['employees']);
            $this->addResult('employee_search', true, "Recherche réussie - {$count} résultats");
            echo "   ✅ Recherche réussie - {$count} résultats trouvés\n";
        } else {
            $this->addResult('employee_search', false, 'Échec de la recherche');
            echo "   ❌ Échec de la recherche\n";
        }
        echo "\n";
    }

    private function testEmployeePhotoUpdate()
    {
        echo "📸 Test 4: Mise à jour photo employé\n";
        
        // D'abord, récupérer la liste des employés pour avoir un ID
        $employeesResponse = $this->makeRequest('GET', '/employees');
        
        if ($employeesResponse && isset($employeesResponse['data']['data']) && !empty($employeesResponse['data']['data'])) {
            $employeeId = $employeesResponse['data']['data'][0]['id'];
            
            // Photo factice en base64 (pixel transparent)
            $fakePhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
            
            $photoData = [
                'photo' => $fakePhoto
            ];
            
            $response = $this->makeRequest('PUT', "/employees/{$employeeId}/photo", $photoData);
            
            if ($response && isset($response['success']) && $response['success']) {
                $this->addResult('employee_photo', true, 'Mise à jour photo réussie');
                echo "   ✅ Mise à jour photo réussie pour l'employé {$employeeId}\n";
            } else {
                $this->addResult('employee_photo', false, 'Échec mise à jour photo');
                echo "   ❌ Échec mise à jour photo\n";
            }
        } else {
            $this->addResult('employee_photo', false, 'Aucun employé trouvé pour le test');
            echo "   ⚠️ Aucun employé trouvé pour le test\n";
        }
        echo "\n";
    }

    private function testContractManagement()
    {
        echo "📝 Test 5: Gestion des contrats\n";
        
        // Test récupération des contrats d'un employé
        $employeesResponse = $this->makeRequest('GET', '/employees');
        
        if ($employeesResponse && isset($employeesResponse['data']['data']) && !empty($employeesResponse['data']['data'])) {
            $employeeId = $employeesResponse['data']['data'][0]['id'];
            
            // Test historique des contrats
            $contractsResponse = $this->makeRequest('GET', "/employees/{$employeeId}/contracts");
            
            if ($contractsResponse && isset($contractsResponse['success']) && $contractsResponse['success']) {
                $contractCount = count($contractsResponse['data']['contracts']);
                $this->addResult('employee_contracts', true, "Historique contrats récupéré - {$contractCount} contrats");
                echo "   ✅ Historique contrats récupéré - {$contractCount} contrats\n";
                
                // Test création d'un nouveau contrat
                $newContractData = [
                    'type_emploi' => 'Ménage',
                    'salaire_mensuel' => 50000,
                    'date_debut' => date('Y-m-d', strtotime('+1 day'))
                ];
                
                $newContractResponse = $this->makeRequest('POST', "/employees/{$employeeId}/contracts", $newContractData);
                
                if ($newContractResponse && isset($newContractResponse['success'])) {
                    if ($newContractResponse['success']) {
                        $this->addResult('contract_create', true, 'Nouveau contrat créé');
                        echo "   ✅ Nouveau contrat créé\n";
                        
                        $contractId = $newContractResponse['data']['contract']['id'];
                        
                        // Test terminaison de contrat
                        $terminateData = [
                            'date_fin' => date('Y-m-d', strtotime('+30 days')),
                            'motif' => 'Test de terminaison de contrat'
                        ];
                        
                        $terminateResponse = $this->makeRequest('PUT', "/contracts/{$contractId}/terminate", $terminateData);
                        
                        if ($terminateResponse && isset($terminateResponse['success']) && $terminateResponse['success']) {
                            $this->addResult('contract_terminate', true, 'Contrat terminé avec succès');
                            echo "   ✅ Contrat terminé avec succès\n";
                        } else {
                            $this->addResult('contract_terminate', false, 'Échec terminaison contrat');
                            echo "   ❌ Échec terminaison contrat\n";
                        }
                    } else {
                        $message = $newContractResponse['message'] ?? 'Erreur inconnue';
                        $this->addResult('contract_create', false, "Échec création contrat: {$message}");
                        echo "   ❌ Échec création contrat: {$message}\n";
                    }
                } else {
                    $this->addResult('contract_create', false, 'Erreur requête création contrat');
                    echo "   ❌ Erreur requête création contrat\n";
                }
            } else {
                $this->addResult('employee_contracts', false, 'Échec récupération contrats');
                echo "   ❌ Échec récupération contrats\n";
            }
        } else {
            $this->addResult('contract_management', false, 'Aucun employé trouvé');
            echo "   ⚠️ Aucun employé trouvé pour tester les contrats\n";
        }
        echo "\n";
    }

    private function testDashboardStats()
    {
        echo "📊 Test 6: Dashboard et statistiques\n";
        
        // Test statistiques complètes
        $statsResponse = $this->makeRequest('GET', '/dashboard/stats');
        
        if ($statsResponse && isset($statsResponse['success']) && $statsResponse['success']) {
            $totalEmployees = $statsResponse['data']['overview']['total_employees'];
            $this->addResult('dashboard_stats', true, "Statistiques récupérées - {$totalEmployees} employés");
            echo "   ✅ Statistiques récupérées - {$totalEmployees} employés\n";
        } else {
            $this->addResult('dashboard_stats', false, 'Échec récupération statistiques');
            echo "   ❌ Échec récupération statistiques\n";
        }
        
        // Test résumé dashboard
        $summaryResponse = $this->makeRequest('GET', '/dashboard/summary');
        
        if ($summaryResponse && isset($summaryResponse['success']) && $summaryResponse['success']) {
            $activeContracts = $summaryResponse['data']['active_contracts'];
            $this->addResult('dashboard_summary', true, "Résumé récupéré - {$activeContracts} contrats actifs");
            echo "   ✅ Résumé récupéré - {$activeContracts} contrats actifs\n";
        } else {
            $this->addResult('dashboard_summary', false, 'Échec récupération résumé');
            echo "   ❌ Échec récupération résumé\n";
        }
        
        // Test export employés
        $exportResponse = $this->makeRequest('GET', '/reports/employees?format=json');
        
        if ($exportResponse && isset($exportResponse['success']) && $exportResponse['success']) {
            $recordCount = $exportResponse['data']['total_records'];
            $this->addResult('employee_export', true, "Export réussi - {$recordCount} enregistrements");
            echo "   ✅ Export réussi - {$recordCount} enregistrements\n";
        } else {
            $this->addResult('employee_export', false, 'Échec export employés');
            echo "   ❌ Échec export employés\n";
        }
        echo "\n";
    }

    private function testMonthlyConfirmations()
    {
        echo "✅ Test 7: Confirmations mensuelles\n";
        
        // Test confirmations en attente
        $pendingResponse = $this->makeRequest('GET', '/monthly-confirmations/pending');
        
        if ($pendingResponse && isset($pendingResponse['success']) && $pendingResponse['success']) {
            $pendingCount = $pendingResponse['data']['total_pending'];
            $this->addResult('confirmations_pending', true, "Confirmations en attente: {$pendingCount}");
            echo "   ✅ Confirmations en attente récupérées: {$pendingCount}\n";
        } else {
            $this->addResult('confirmations_pending', false, 'Échec récupération confirmations en attente');
            echo "   ❌ Échec récupération confirmations en attente\n";
        }
        
        // Test statistiques confirmations
        $statsResponse = $this->makeRequest('GET', '/monthly-confirmations/statistics');
        
        if ($statsResponse && isset($statsResponse['success']) && $statsResponse['success']) {
            $totalConfirmations = $statsResponse['data']['total_confirmations'];
            $this->addResult('confirmations_stats', true, "Statistiques confirmations: {$totalConfirmations}");
            echo "   ✅ Statistiques confirmations: {$totalConfirmations}\n";
        } else {
            $this->addResult('confirmations_stats', false, 'Échec statistiques confirmations');
            echo "   ❌ Échec statistiques confirmations\n";
        }
        
        // Test confirmation directe d'un employé
        $employeesResponse = $this->makeRequest('GET', '/employees');
        
        if ($employeesResponse && isset($employeesResponse['data']['data']) && !empty($employeesResponse['data']['data'])) {
            $employeeId = $employeesResponse['data']['data'][0]['id'];
            
            $confirmationData = [
                'mois' => (int)date('n'),
                'annee' => (int)date('Y'),
                'commentaire' => 'Test de confirmation automatique'
            ];
            
            $confirmResponse = $this->makeRequest('POST', "/employees/{$employeeId}/monthly-confirmations", $confirmationData);
            
            if ($confirmResponse && isset($confirmResponse['success'])) {
                if ($confirmResponse['success']) {
                    $this->addResult('employee_confirmation', true, 'Confirmation employé réussie');
                    echo "   ✅ Confirmation employé réussie\n";
                } else {
                    $message = $confirmResponse['message'] ?? 'Erreur inconnue';
                    $this->addResult('employee_confirmation', false, "Échec confirmation: {$message}");
                    echo "   ⚠️ Échec confirmation (probablement déjà confirmé): {$message}\n";
                }
            } else {
                $this->addResult('employee_confirmation', false, 'Erreur requête confirmation');
                echo "   ❌ Erreur requête confirmation\n";
            }
        }
        echo "\n";
    }

    private function testProfileDocuments()
    {
        echo "📄 Test 8: Documents profil employeur\n";
        
        // Document factice en base64 (petit PDF)
        $fakeDocument = 'data:application/pdf;base64,JVBERi0xLjQKJcOBw6DCgsOCw4PDhMOFw4bDh8OIw4nDisOLw4zDjcOOw4/DkA==';
        
        $documentData = [
            'type_document' => 'piece_identite',
            'document' => $fakeDocument,
            'nom_document' => 'Test_Document_ID.pdf'
        ];
        
        $response = $this->makeRequest('POST', '/profile/documents', $documentData);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->addResult('profile_documents', true, 'Upload document profil réussi');
            echo "   ✅ Upload document profil réussi\n";
        } else {
            $this->addResult('profile_documents', false, 'Échec upload document profil');
            echo "   ❌ Échec upload document profil\n";
        }
        echo "\n";
    }

    private function makeRequest($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Data
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            echo "   ❌ Erreur cURL: " . curl_error($ch) . "\n";
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            echo "   ⚠️ HTTP {$httpCode}: " . ($decodedResponse['message'] ?? 'Erreur inconnue') . "\n";
        }
        
        return $decodedResponse;
    }

    private function addResult($test, $success, $message)
    {
        $this->results[] = [
            'test' => $test,
            'success' => $success,
            'message' => $message
        ];
    }

    private function showResults()
    {
        echo "📋 RÉSUMÉ DES TESTS\n";
        echo str_repeat("=", 60) . "\n";
        
        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r['success']));
        $failed = $total - $passed;
        
        foreach ($this->results as $result) {
            $status = $result['success'] ? '✅' : '❌';
            echo sprintf("%-30s %s %s\n", $result['test'], $status, $result['message']);
        }
        
        echo str_repeat("=", 60) . "\n";
        echo "TOTAL: {$total} tests | RÉUSSIS: {$passed} | ÉCHOUÉS: {$failed}\n";
        
        if ($failed === 0) {
            echo "🎉 Tous les tests sont passés avec succès !\n";
        } else {
            echo "⚠️ {$failed} test(s) ont échoué. Vérifiez les détails ci-dessus.\n";
        }
        
        echo "\n💡 NOTES:\n";
        echo "- Assurez-vous d'avoir des données de test dans votre base\n";
        echo "- Modifiez les identifiants de connexion si nécessaire\n";
        echo "- Certains échecs peuvent être normaux (ex: confirmation déjà existante)\n";
    }
}

// Configuration
$baseUrl = 'http://localhost/sgreweb/public/api/v1'; // Modifier selon votre environnement

// Lancement des tests
$tester = new ApiTester($baseUrl);
$tester->runAllTests();