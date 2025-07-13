# 📱 Guide d'intégration des nouveaux endpoints API dans Flutter

## 🎯 Objectif
Ce document détaille l'intégration des nouveaux endpoints API (contrats et nationalités) dans l'application Flutter SGRE.

---

## 📋 Nouveaux endpoints à intégrer

### 🔧 **CONTRATS**
```
GET /api/v1/contracts              - Liste des contrats
PUT /api/v1/contracts/{id}         - Modifier un contrat
```

### 🌍 **NATIONALITÉS**
```
GET /api/v1/nationalities          - Liste publique des nationalités
POST /api/v1/nationalities         - Créer une nationalité (admin)
GET /api/v1/nationalities/{id}     - Détails d'une nationalité (admin)
PUT /api/v1/nationalities/{id}     - Modifier une nationalité (admin)
DELETE /api/v1/nationalities/{id}  - Supprimer une nationalité (admin)
GET /api/v1/nationalities/statistics - Statistiques des nationalités (admin)
```

---

## 🏗️ Architecture Flutter recommandée

### 📁 Structure des dossiers
```
lib/
├── models/
│   ├── contract_model.dart           # Nouveau
│   ├── nationality_model.dart        # Nouveau
│   └── api_response_model.dart       # Existant
├── services/
│   ├── contract_service.dart         # Nouveau
│   ├── nationality_service.dart      # Nouveau
│   └── api_service.dart              # Existant - à étendre
├── controllers/
│   ├── contract_controller.dart      # Nouveau
│   ├── nationality_controller.dart   # Nouveau
│   └── auth_controller.dart          # Existant
├── screens/
│   ├── contracts/
│   │   ├── contracts_list_screen.dart    # Nouveau
│   │   ├── contract_detail_screen.dart   # Nouveau
│   │   └── contract_edit_screen.dart     # Nouveau
│   └── admin/
│       ├── nationalities_screen.dart     # Nouveau
│       └── nationality_form_screen.dart  # Nouveau
└── widgets/
    ├── contract_card.dart            # Nouveau
    ├── nationality_dropdown.dart     # Nouveau
    └── loading_widget.dart           # Existant
```

---

## 📊 Modèles de données

### 1. Contract Model
```dart
// lib/models/contract_model.dart
class Contract {
  final int id;
  final String typeEmploi;
  final double salaireMensuel;
  final DateTime dateDebut;
  final DateTime? dateFin;
  final String? motifFin;
  final bool estActif;
  final int dureeJours;
  final int dureeMois;
  final Employee employee;
  final DateTime createdAt;

  Contract({
    required this.id,
    required this.typeEmploi,
    required this.salaireMensuel,
    required this.dateDebut,
    this.dateFin,
    this.motifFin,
    required this.estActif,
    required this.dureeJours,
    required this.dureeMois,
    required this.employee,
    required this.createdAt,
  });

  factory Contract.fromJson(Map<String, dynamic> json) {
    return Contract(
      id: json['id'],
      typeEmploi: json['type_emploi'],
      salaireMensuel: double.parse(json['salaire_mensuel'].toString()),
      dateDebut: DateTime.parse(json['date_debut']),
      dateFin: json['date_fin'] != null ? DateTime.parse(json['date_fin']) : null,
      motifFin: json['motif_fin'],
      estActif: json['est_actif'],
      dureeJours: json['duree_jours'],
      dureeMois: json['duree_mois'],
      employee: Employee.fromJson(json['employee']),
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type_emploi': typeEmploi,
      'salaire_mensuel': salaireMensuel,
      'date_debut': dateDebut.toIso8601String().split('T')[0],
      'date_fin': dateFin?.toIso8601String().split('T')[0],
      'motif_fin': motifFin,
      'est_actif': estActif,
    };
  }
}

class ContractListResponse {
  final List<Contract> contracts;
  final int currentPage;
  final int lastPage;
  final int total;
  final int perPage;

  ContractListResponse({
    required this.contracts,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.perPage,
  });

  factory ContractListResponse.fromJson(Map<String, dynamic> json) {
    return ContractListResponse(
      contracts: (json['data'] as List)
          .map((contract) => Contract.fromJson(contract))
          .toList(),
      currentPage: json['current_page'],
      lastPage: json['last_page'],
      total: json['total'],
      perPage: json['per_page'],
    );
  }
}
```

### 2. Nationality Model
```dart
// lib/models/nationality_model.dart
class Nationality {
  final int id;
  final String nom;
  final String code;
  final bool isActive;
  final int? employeesCount;
  final int? activeEmployeesCount;

  Nationality({
    required this.id,
    required this.nom,
    required this.code,
    this.isActive = true,
    this.employeesCount,
    this.activeEmployeesCount,
  });

  factory Nationality.fromJson(Map<String, dynamic> json) {
    return Nationality(
      id: json['id'],
      nom: json['nom'],
      code: json['code'],
      isActive: json['is_active'] ?? true,
      employeesCount: json['employees_count'],
      activeEmployeesCount: json['active_employees_count'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'nom': nom,
      'code': code,
      'is_active': isActive,
    };
  }
}

class NationalityStats {
  final int totalNationalities;
  final int totalEmployees;
  final int totalActiveEmployees;
  final List<Nationality> nationalities;

  NationalityStats({
    required this.totalNationalities,
    required this.totalEmployees,
    required this.totalActiveEmployees,
    required this.nationalities,
  });

  factory NationalityStats.fromJson(Map<String, dynamic> json) {
    return NationalityStats(
      totalNationalities: json['total_nationalities'],
      totalEmployees: json['total_employees'],
      totalActiveEmployees: json['total_active_employees'],
      nationalities: (json['nationalities'] as List)
          .map((nat) => Nationality.fromJson(nat))
          .toList(),
    );
  }
}
```

---

## 🌐 Services API

### 1. Contract Service
```dart
// lib/services/contract_service.dart
import 'package:dio/dio.dart';
import '../models/contract_model.dart';
import '../models/api_response_model.dart';
import 'api_service.dart';

class ContractService {
  final ApiService _apiService = ApiService();

  // Liste des contrats avec filtres
  Future<ApiResponse<ContractListResponse>> getContracts({
    int page = 1,
    int perPage = 15,
    String? status, // 'active', 'terminated'
    int? employeeId,
    String sortBy = 'date_debut',
    String sortDirection = 'desc',
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
      };

      if (status != null) queryParams['status'] = status;
      if (employeeId != null) queryParams['employee_id'] = employeeId;

      final response = await _apiService.get(
        '/contracts',
        queryParameters: queryParams,
      );

      if (response.data['success']) {
        return ApiResponse.success(
          ContractListResponse.fromJson(response.data['data']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Détails d'un contrat
  Future<ApiResponse<Contract>> getContract(int id) async {
    try {
      final response = await _apiService.get('/contracts/$id');

      if (response.data['success']) {
        return ApiResponse.success(
          Contract.fromJson(response.data['data']['contract']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Modifier un contrat
  Future<ApiResponse<Contract>> updateContract(
    int id,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _apiService.put('/contracts/$id', data: data);

      if (response.data['success']) {
        return ApiResponse.success(
          Contract.fromJson(response.data['data']['contract']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Terminer un contrat
  Future<ApiResponse<Contract>> terminateContract(
    int id, {
    required DateTime dateFin,
    required String motif,
  }) async {
    try {
      final data = {
        'date_fin': dateFin.toIso8601String().split('T')[0],
        'motif': motif,
      };

      final response = await _apiService.put('/contracts/$id/terminate', data: data);

      if (response.data['success']) {
        return ApiResponse.success(
          Contract.fromJson(response.data['data']['contract']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }
}
```

### 2. Nationality Service
```dart
// lib/services/nationality_service.dart
import 'package:dio/dio.dart';
import '../models/nationality_model.dart';
import '../models/api_response_model.dart';
import 'api_service.dart';

class NationalityService {
  final ApiService _apiService = ApiService();

  // Liste publique des nationalités (sans auth)
  Future<ApiResponse<List<Nationality>>> getNationalities({
    bool all = true,
    String? search,
    String sortBy = 'nom',
    String sortDirection = 'asc',
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'sort_by': sortBy,
        'sort_direction': sortDirection,
      };

      if (all) queryParams['all'] = true;
      if (search != null) queryParams['search'] = search;

      final response = await _apiService.getPublic(
        '/nationalities',
        queryParameters: queryParams,
      );

      if (response.data['success']) {
        final List<dynamic> data = all 
            ? response.data['data'] 
            : response.data['data']['data'];
        
        return ApiResponse.success(
          data.map((nat) => Nationality.fromJson(nat)).toList(),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // MÉTHODES ADMIN (authentification requise)

  // Créer une nationalité
  Future<ApiResponse<Nationality>> createNationality({
    required String nom,
    required String code,
    bool isActive = true,
  }) async {
    try {
      final data = {
        'nom': nom,
        'code': code.toUpperCase(),
        'is_active': isActive,
      };

      final response = await _apiService.post('/nationalities', data: data);

      if (response.data['success']) {
        return ApiResponse.success(
          Nationality.fromJson(response.data['data']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Détails d'une nationalité
  Future<ApiResponse<Nationality>> getNationality(int id) async {
    try {
      final response = await _apiService.get('/nationalities/$id');

      if (response.data['success']) {
        return ApiResponse.success(
          Nationality.fromJson(response.data['data']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Modifier une nationalité
  Future<ApiResponse<Nationality>> updateNationality(
    int id,
    Map<String, dynamic> data,
  ) async {
    try {
      if (data.containsKey('code')) {
        data['code'] = data['code'].toString().toUpperCase();
      }

      final response = await _apiService.put('/nationalities/$id', data: data);

      if (response.data['success']) {
        return ApiResponse.success(
          Nationality.fromJson(response.data['data']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Supprimer une nationalité
  Future<ApiResponse<bool>> deleteNationality(int id) async {
    try {
      final response = await _apiService.delete('/nationalities/$id');

      if (response.data['success']) {
        return ApiResponse.success(true, response.data['message']);
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }

  // Statistiques des nationalités
  Future<ApiResponse<NationalityStats>> getNationalityStats() async {
    try {
      final response = await _apiService.get('/nationalities/statistics');

      if (response.data['success']) {
        return ApiResponse.success(
          NationalityStats.fromJson(response.data['data']),
          response.data['message'],
        );
      } else {
        return ApiResponse.error(response.data['message']);
      }
    } on DioException catch (e) {
      return ApiResponse.error(_apiService.handleDioError(e));
    }
  }
}
```

### 3. Extension ApiService
```dart
// Ajouter cette méthode à votre ApiService existant
class ApiService {
  // ... méthodes existantes ...

  // Méthode pour appels publics (sans token d'auth)
  Future<Response> getPublic(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.get(
        endpoint,
        queryParameters: queryParameters,
        options: Options(
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ),
      );
      return response;
    } catch (e) {
      rethrow;
    }
  }
}
```

---

## 🎛️ Contrôleurs GetX

### 1. Contract Controller
```dart
// lib/controllers/contract_controller.dart
import 'package:get/get.dart';
import '../models/contract_model.dart';
import '../services/contract_service.dart';

class ContractController extends GetxController {
  final ContractService _contractService = ContractService();

  // Observable variables
  final contracts = <Contract>[].obs;
  final isLoading = false.obs;
  final currentPage = 1.obs;
  final hasMoreData = true.obs;
  final selectedStatus = Rxn<String>();

  // Pagination
  final int perPage = 15;

  @override
  void onInit() {
    super.onInit();
    loadContracts();
  }

  // Charger les contrats
  Future<void> loadContracts({bool refresh = false}) async {
    if (refresh) {
      currentPage.value = 1;
      hasMoreData.value = true;
      contracts.clear();
    }

    if (!hasMoreData.value) return;

    isLoading.value = true;

    final response = await _contractService.getContracts(
      page: currentPage.value,
      perPage: perPage,
      status: selectedStatus.value,
      sortBy: 'date_debut',
      sortDirection: 'desc',
    );

    if (response.isSuccess) {
      final newContracts = response.data!.contracts;
      
      if (refresh) {
        contracts.value = newContracts;
      } else {
        contracts.addAll(newContracts);
      }

      hasMoreData.value = newContracts.length == perPage;
      currentPage.value++;
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // Filtrer par statut
  void filterByStatus(String? status) {
    selectedStatus.value = status;
    loadContracts(refresh: true);
  }

  // Modifier un contrat
  Future<void> updateContract(int id, Map<String, dynamic> data) async {
    isLoading.value = true;

    final response = await _contractService.updateContract(id, data);

    if (response.isSuccess) {
      // Mettre à jour la liste locale
      final index = contracts.indexWhere((c) => c.id == id);
      if (index != -1) {
        contracts[index] = response.data!;
      }
      Get.snackbar('Succès', response.message);
      Get.back(); // Retour à la liste
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // Terminer un contrat
  Future<void> terminateContract(
    int id, {
    required DateTime dateFin,
    required String motif,
  }) async {
    isLoading.value = true;

    final response = await _contractService.terminateContract(
      id,
      dateFin: dateFin,
      motif: motif,
    );

    if (response.isSuccess) {
      // Mettre à jour la liste locale
      final index = contracts.indexWhere((c) => c.id == id);
      if (index != -1) {
        contracts[index] = response.data!;
      }
      Get.snackbar('Succès', 'Contrat terminé avec succès');
      Get.back();
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // Rafraîchir
  Future<void> refresh() async {
    await loadContracts(refresh: true);
  }

  // Charger plus
  Future<void> loadMore() async {
    await loadContracts();
  }
}
```

### 2. Nationality Controller
```dart
// lib/controllers/nationality_controller.dart
import 'package:get/get.dart';
import '../models/nationality_model.dart';
import '../services/nationality_service.dart';

class NationalityController extends GetxController {
  final NationalityService _nationalityService = NationalityService();

  // Observable variables
  final nationalities = <Nationality>[].obs;
  final nationalityStats = Rxn<NationalityStats>();
  final isLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    loadNationalities();
  }

  // Charger les nationalités (public)
  Future<void> loadNationalities() async {
    isLoading.value = true;

    final response = await _nationalityService.getNationalities(all: true);

    if (response.isSuccess) {
      nationalities.value = response.data!;
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // MÉTHODES ADMIN

  // Charger les statistiques
  Future<void> loadStats() async {
    isLoading.value = true;

    final response = await _nationalityService.getNationalityStats();

    if (response.isSuccess) {
      nationalityStats.value = response.data!;
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // Créer une nationalité
  Future<void> createNationality({
    required String nom,
    required String code,
  }) async {
    isLoading.value = true;

    final response = await _nationalityService.createNationality(
      nom: nom,
      code: code,
    );

    if (response.isSuccess) {
      nationalities.add(response.data!);
      Get.snackbar('Succès', response.message);
      Get.back();
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // Modifier une nationalité
  Future<void> updateNationality(int id, Map<String, dynamic> data) async {
    isLoading.value = true;

    final response = await _nationalityService.updateNationality(id, data);

    if (response.isSuccess) {
      final index = nationalities.indexWhere((n) => n.id == id);
      if (index != -1) {
        nationalities[index] = response.data!;
      }
      Get.snackbar('Succès', response.message);
      Get.back();
    } else {
      Get.snackbar('Erreur', response.message);
    }

    isLoading.value = false;
  }

  // Supprimer une nationalité
  Future<void> deleteNationality(int id) async {
    final result = await Get.dialog<bool>(
      AlertDialog(
        title: const Text('Confirmer la suppression'),
        content: const Text('Êtes-vous sûr de vouloir supprimer cette nationalité ?'),
        actions: [
          TextButton(
            onPressed: () => Get.back(result: false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Get.back(result: true),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (result == true) {
      isLoading.value = true;

      final response = await _nationalityService.deleteNationality(id);

      if (response.isSuccess) {
        nationalities.removeWhere((n) => n.id == id);
        Get.snackbar('Succès', response.message);
      } else {
        Get.snackbar('Erreur', response.message);
      }

      isLoading.value = false;
    }
  }

  // Rafraîchir
  Future<void> refresh() async {
    await loadNationalities();
  }
}
```

---

## 📱 Exemples d'écrans

### 1. Liste des contrats
```dart
// lib/screens/contracts/contracts_list_screen.dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../controllers/contract_controller.dart';
import '../../widgets/contract_card.dart';

class ContractsListScreen extends StatelessWidget {
  const ContractsListScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(ContractController());

    return Scaffold(
      appBar: AppBar(
        title: const Text('Contrats'),
        actions: [
          PopupMenuButton<String>(
            onSelected: controller.filterByStatus,
            itemBuilder: (context) => [
              const PopupMenuItem(value: null, child: Text('Tous')),
              const PopupMenuItem(value: 'active', child: Text('Actifs')),
              const PopupMenuItem(value: 'terminated', child: Text('Terminés')),
            ],
          ),
        ],
      ),
      body: Obx(() {
        if (controller.contracts.isEmpty && controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }

        return RefreshIndicator(
          onRefresh: controller.refresh,
          child: ListView.builder(
            itemCount: controller.contracts.length + 
                       (controller.hasMoreData.value ? 1 : 0),
            itemBuilder: (context, index) {
              if (index == controller.contracts.length) {
                // Loading indicator pour pagination
                controller.loadMore();
                return const Center(
                  child: Padding(
                    padding: EdgeInsets.all(16.0),
                    child: CircularProgressIndicator(),
                  ),
                );
              }

              return ContractCard(
                contract: controller.contracts[index],
                onTap: () => Get.toNamed(
                  '/contract-detail',
                  arguments: controller.contracts[index],
                ),
              );
            },
          ),
        );
      }),
    );
  }
}
```

### 2. Widget Dropdown Nationalités
```dart
// lib/widgets/nationality_dropdown.dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../controllers/nationality_controller.dart';
import '../models/nationality_model.dart';

class NationalityDropdown extends StatelessWidget {
  final int? selectedNationalityId;
  final Function(Nationality?) onChanged;
  final String? hintText;

  const NationalityDropdown({
    Key? key,
    this.selectedNationalityId,
    required this.onChanged,
    this.hintText,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(NationalityController());

    return Obx(() {
      if (controller.isLoading.value) {
        return const DropdownButtonFormField<Nationality>(
          items: [],
          onChanged: null,
          decoration: InputDecoration(
            hintText: 'Chargement...',
            suffixIcon: SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ),
        );
      }

      final selectedNationality = controller.nationalities
          .firstWhereOrNull((n) => n.id == selectedNationalityId);

      return DropdownButtonFormField<Nationality>(
        value: selectedNationality,
        decoration: InputDecoration(
          hintText: hintText ?? 'Sélectionner une nationalité',
          border: const OutlineInputBorder(),
        ),
        items: controller.nationalities.map((nationality) {
          return DropdownMenuItem<Nationality>(
            value: nationality,
            child: Text('${nationality.nom} (${nationality.code})'),
          );
        }).toList(),
        onChanged: onChanged,
        validator: (value) {
          if (value == null) {
            return 'Veuillez sélectionner une nationalité';
          }
          return null;
        },
      );
    });
  }
}
```

---

## 🚀 Instructions d'implémentation

### Étape 1: Modèles
1. Créer `contract_model.dart` avec la classe `Contract`
2. Créer `nationality_model.dart` avec les classes `Nationality` et `NationalityStats`
3. Tester la sérialisation/désérialisation JSON

### Étape 2: Services
1. Créer `contract_service.dart` avec toutes les méthodes API
2. Créer `nationality_service.dart` avec méthodes publiques et admin
3. Étendre `ApiService` avec la méthode `getPublic()`
4. Tester les appels API avec des données mock

### Étape 3: Contrôleurs
1. Implémenter `ContractController` avec gestion pagination
2. Implémenter `NationalityController` avec méthodes CRUD
3. Tester la réactivité et gestion d'erreurs

### Étape 4: Interface utilisateur
1. Créer les écrans de liste et détail pour contrats
2. Créer les écrans admin pour nationalités
3. Implémenter les widgets réutilisables
4. Ajouter les routes dans `app_routes.dart`

### Étape 5: Tests et optimisation
1. Tester tous les flux utilisateur
2. Optimiser les performances (lazy loading, cache)
3. Ajouter gestion offline si nécessaire
4. Valider avec les tests d'intégration

---

## 🔧 Configuration requise

### Dependencies à ajouter
```yaml
dependencies:
  dio: ^5.3.2
  get: ^4.6.6
  # ... autres dépendances existantes
```

### Variables d'environnement
```dart
// config/api_config.dart
class ApiConfig {
  static const String baseUrl = 'http://197.241.32.130:82/api/v1';
  static const Duration timeout = Duration(seconds: 30);
}
```

---

## 📝 Notes importantes

1. **Authentification**: Les endpoints nationalités publics ne nécessitent pas de token
2. **Pagination**: Utiliser la pagination pour les listes longues
3. **Cache**: Implémenter un cache local pour les nationalités
4. **Offline**: Prévoir un mode hors ligne pour la consultation
5. **Validation**: Valider côté client avant envoi API
6. **Erreurs**: Gérer tous les cas d'erreur (réseau, serveur, validation)

---

## 🆘 Support et débogage

### Tests des endpoints
Utiliser le fichier `test_endpoints_nouveaux.php` pour valider l'API.

### Logging
Activer les logs Dio pour déboguer les appels API:
```dart
dio.interceptors.add(LogInterceptor(
  requestBody: true,
  responseBody: true,
));
```

### Points de vérification
- [ ] Modèles sérialisent correctement
- [ ] Services gèrent les erreurs
- [ ] Contrôleurs maintiennent l'état
- [ ] Interface réactive aux changements
- [ ] Navigation fonctionne
- [ ] Formulaires valident les données

---

**🎯 Ce guide couvre l'intégration complète des nouveaux endpoints dans Flutter. Suivez les étapes dans l'ordre pour une implémentation réussie.**