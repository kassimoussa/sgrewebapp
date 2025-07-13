# 📱 NOUVEAUX ENDPOINTS SGRE - Guide d'Intégration Mobile

> Documentation complète des 13 nouveaux endpoints API pour intégration dans l'application mobile existante.

## 🔗 Base URL
```
https://api.sgre.dj/api/v1
```

## 🔐 Authentification
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## 📋 LISTE COMPLÈTE DES NOUVEAUX ENDPOINTS

### 1. **POST** `/auth/refresh` - Renouvellement de token

### 2. **PUT** `/employees/{id}/photo` - Mise à jour photo employé

### 3. **POST** `/employees/search` - Recherche employés existants

### 4. **POST** `/employees/{id}/register-existing` - Ré-enregistrer employé existant

### 5. **GET** `/employees/{id}/contracts` - Historique contrats employé

### 6. **POST** `/employees/{id}/contracts` - Créer nouveau contrat

### 7. **GET** `/contracts/{id}` - Détails d'un contrat

### 8. **PUT** `/contracts/{id}/terminate` - Terminer un contrat

### 9. **GET** `/dashboard/stats` - Statistiques complètes employeur

### 10. **GET** `/dashboard/summary` - Résumé rapide dashboard

### 11. **GET** `/reports/employees` - Export données employés

### 12. **GET** `/monthly-confirmations/pending` - Confirmations en attente

### 13. **POST** `/employees/{id}/monthly-confirmations` - Confirmer employé directement

### 14. **POST** `/profile/documents` - Upload documents profil employeur

---

## 📖 DÉTAILS PAR ENDPOINT

### 1. 🔄 **Renouvellement Token**

**Endpoint:** `POST /auth/refresh`

**Usage mobile:** Renouveler automatiquement le token avant expiration

**Requête:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Token renouvelé avec succès",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

**Réponse Erreur (401):**
```json
{
  "success": false,
  "message": "Token invalide ou expiré"
}
```

**Implémentation Flutter:**
```dart
// Auto-refresh token avant expiration
Future<void> refreshTokenIfNeeded() async {
  if (tokenWillExpireSoon()) {
    await authService.refreshToken();
  }
}
```

---

### 2. 📸 **Mise à jour Photo Employé**

**Endpoint:** `PUT /employees/{id}/photo`

**Usage mobile:** Permettre la mise à jour de photo seule (caméra/galerie)

**Requête:**
```json
{
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ..."
}
```

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Photo mise à jour avec succès",
  "data": {
    "employee": {
      "id": 1,
      "photo_url": "https://api.sgre.dj/storage/employees/photos/photo_1_1656789123.jpg"
    }
  }
}
```

**Réponse Erreur (404):**
```json
{
  "success": false,
  "message": "Employé non trouvé ou vous n'avez pas l'autorisation"
}
```

**Implémentation Flutter:**
```dart
// Sélection et upload de photo
Future<void> updateEmployeePhoto(int employeeId) async {
  final image = await ImagePicker().pickImage(source: ImageSource.camera);
  if (image != null) {
    final base64 = await convertToBase64(image);
    await employeeService.updatePhoto(employeeId, base64);
  }
}
```

---

### 3. 🔍 **Recherche Employés Existants**

**Endpoint:** `POST /employees/search`

**Usage mobile:** Rechercher avant ré-enregistrement ou pour éviter doublons

**Requête:**
```json
{
  "employee_id": "EMP001",     // optionnel
  "prenom": "Marie",           // optionnel
  "nom": "Doe",               // optionnel
  "telephone": "+25377123456", // optionnel
  "date_naissance": "1990-05-15" // optionnel
}
```

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Recherche effectuée avec succès",
  "data": {
    "employees": [
      {
        "id": 1,
        "prenom": "Marie",
        "nom": "Doe",
        "date_naissance": "1990-05-15",
        "telephone": "+25377123456",
        "nationalite": "Française",
        "current_employer": "Jean Dupont",
        "can_register": false,
        "photo_url": "https://api.sgre.dj/storage/employees/photos/photo_1.jpg"
      }
    ]
  }
}
```

**Implémentation Flutter:**
```dart
// Interface de recherche avec suggestions
Widget buildEmployeeSearchDialog() {
  return AlertDialog(
    title: Text('Rechercher un employé existant'),
    content: Column(
      children: [
        TextField(
          decoration: InputDecoration(labelText: 'Prénom'),
          onChanged: (value) => searchCriteria.prenom = value,
        ),
        // Autres champs...
        ElevatedButton(
          onPressed: () => performSearch(),
          child: Text('Rechercher'),
        )
      ],
    ),
  );
}
```

---

### 4. 🔄 **Ré-enregistrer Employé Existant**

**Endpoint:** `POST /employees/{id}/register-existing`

**Usage mobile:** Créer nouveau contrat pour employé déjà dans le système

**Requête:**
```json
{
  "contract": {
    "type_emploi": "Ménage",
    "salaire_mensuel": 45000,
    "date_debut": "2024-08-01"
  }
}
```

**Réponse Succès (201):**
```json
{
  "success": true,
  "message": "Employé enregistré avec succès",
  "data": {
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe",
      "is_active": true
    },
    "contract": {
      "id": 15,
      "type_emploi": "Ménage",
      "salaire_mensuel": 45000,
      "date_debut": "2024-08-01",
      "est_actif": true
    }
  }
}
```

**Réponse Erreur (400):**
```json
{
  "success": false,
  "message": "Cet employé a déjà un contrat actif avec un autre employeur"
}
```

---

### 5. 📜 **Historique Contrats Employé**

**Endpoint:** `GET /employees/{id}/contracts`

**Usage mobile:** Afficher timeline des contrats dans profil employé

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Historique des contrats récupéré avec succès",
  "data": {
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe"
    },
    "contracts": [
      {
        "id": 1,
        "type_emploi": "Ménage",
        "salaire_mensuel": 45000,
        "date_debut": "2024-01-01",
        "date_fin": "2024-06-30",
        "est_actif": false,
        "motif_fin": "Fin de contrat",
        "duree_mois": 6,
        "created_at": "2024-01-01T08:00:00Z"
      },
      {
        "id": 2,
        "type_emploi": "Gardien",
        "salaire_mensuel": 30000,
        "date_debut": "2024-07-01",
        "date_fin": null,
        "est_actif": true,
        "motif_fin": null,
        "duree_mois": 1,
        "created_at": "2024-07-01T09:00:00Z"
      }
    ]
  }
}
```

**Implémentation Flutter:**
```dart
// Timeline widget pour historique contrats
Widget buildContractTimeline(List<Contract> contracts) {
  return Timeline.builder(
    itemCount: contracts.length,
    itemBuilder: (context, index) {
      final contract = contracts[index];
      return TimelineTile(
        indicator: CircleAvatar(
          backgroundColor: contract.isActive ? Colors.green : Colors.grey,
          child: Icon(Icons.work),
        ),
        endChild: ContractCard(contract: contract),
      );
    },
  );
}
```

---

### 6. ➕ **Créer Nouveau Contrat**

**Endpoint:** `POST /employees/{id}/contracts`

**Usage mobile:** Formulaire création contrat pour employé existant

**Requête:**
```json
{
  "type_emploi": "Ménage",
  "salaire_mensuel": 50000,
  "date_debut": "2024-08-01"
}
```

**Réponse Succès (201):**
```json
{
  "success": true,
  "message": "Contrat créé avec succès",
  "data": {
    "contract": {
      "id": 16,
      "type_emploi": "Ménage",
      "salaire_mensuel": 50000,
      "date_debut": "2024-08-01",
      "date_fin": null,
      "est_actif": true,
      "created_at": "2024-07-15T10:30:00Z"
    },
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe"
    }
  }
}
```

**Réponse Erreur (400):**
```json
{
  "success": false,
  "message": "Cet employé a déjà un contrat actif avec vous"
}
```

---

### 7. 📄 **Détails Contrat**

**Endpoint:** `GET /contracts/{id}`

**Usage mobile:** Écran détaillé d'un contrat avec confirmations

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Détails du contrat récupérés avec succès",
  "data": {
    "contract": {
      "id": 1,
      "type_emploi": "Ménage",
      "salaire_mensuel": 45000,
      "date_debut": "2024-01-01",
      "date_fin": null,
      "motif_fin": null,
      "est_actif": true,
      "duree_jours": 195,
      "duree_mois": 6,
      "created_at": "2024-01-01T08:00:00Z"
    },
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe",
      "nationalite": "Française"
    },
    "confirmations": [
      {
        "id": 1,
        "mois": 7,
        "annee": 2024,
        "date_confirmation": "2024-07-15T10:00:00Z"
      }
    ]
  }
}
```

---

### 8. ❌ **Terminer Contrat**

**Endpoint:** `PUT /contracts/{id}/terminate`

**Usage mobile:** Dialog de terminaison avec motif obligatoire

**Requête:**
```json
{
  "date_fin": "2024-12-31",
  "motif": "Fin de contrat à l'amiable"
}
```

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Contrat terminé avec succès",
  "data": {
    "contract": {
      "id": 1,
      "type_emploi": "Ménage",
      "salaire_mensuel": 45000,
      "date_debut": "2024-01-01",
      "date_fin": "2024-12-31",
      "motif_fin": "Fin de contrat à l'amiable",
      "est_actif": false,
      "duree_mois": 12
    },
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe",
      "is_active": false
    }
  }
}
```

**Implémentation Flutter:**
```dart
// Dialog de terminaison
void showTerminateDialog(Contract contract) {
  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Terminer le contrat'),
      content: Column(
        children: [
          DatePicker(
            label: 'Date de fin',
            onDateSelected: (date) => terminationDate = date,
          ),
          TextField(
            decoration: InputDecoration(labelText: 'Motif de fin'),
            onChanged: (value) => terminationReason = value,
            maxLines: 3,
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('Annuler'),
        ),
        ElevatedButton(
          onPressed: () => terminateContract(),
          child: Text('Terminer'),
        ),
      ],
    ),
  );
}
```

---

### 9. 📊 **Statistiques Dashboard Complètes**

**Endpoint:** `GET /dashboard/stats`

**Usage mobile:** Écran principal avec graphiques et métriques

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Statistiques récupérées avec succès",
  "data": {
    "overview": {
      "total_employees": 5,
      "active_contracts": 4,
      "expired_contracts": 1,
      "pending_confirmations": 2
    },
    "employees_by_type": {
      "Ménage": 3,
      "Gardien": 1,
      "Jardinier": 1
    },
    "employees_by_nationality": {
      "Française": 2,
      "Éthiopienne": 2,
      "Djiboutienne": 1
    },
    "employee_evolution": [
      {
        "month": "2024-01",
        "month_name": "Jan 2024",
        "count": 2
      },
      {
        "month": "2024-02",
        "month_name": "Feb 2024",
        "count": 3
      }
    ],
    "average_salaries": {
      "Ménage": 45000,
      "Gardien": 30000,
      "Jardinier": 25000
    },
    "confirmation_statistics": [
      {
        "month": "2024-06",
        "month_name": "Jun 2024",
        "total_contracts": 4,
        "confirmed": 3,
        "confirmation_rate": 75.0
      }
    ],
    "generated_at": "2024-07-15T10:30:00Z"
  }
}
```

**Implémentation Flutter:**
```dart
// Dashboard avec graphiques
Widget buildDashboard(DashboardStats stats) {
  return SingleChildScrollView(
    child: Column(
      children: [
        // Cards overview
        buildOverviewCards(stats.overview),
        
        // Graphique camembert par type
        PieChart(
          dataSource: stats.employeesByType,
          xValueMapper: (data, _) => data.type,
          yValueMapper: (data, _) => data.count,
        ),
        
        // Graphique évolution
        SfCartesianChart(
          series: <LineSeries>[
            LineSeries<EmployeeEvolution, String>(
              dataSource: stats.employeeEvolution,
              xValueMapper: (data, _) => data.monthName,
              yValueMapper: (data, _) => data.count,
            )
          ],
        ),
        
        // Taux de confirmation
        buildConfirmationRateChart(stats.confirmationStatistics),
      ],
    ),
  );
}
```

---

### 10. 📱 **Résumé Dashboard Rapide**

**Endpoint:** `GET /dashboard/summary`

**Usage mobile:** Widget résumé ou notifications push

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Résumé du tableau de bord récupéré avec succès",
  "data": {
    "active_contracts": 4,
    "pending_confirmations": 2,
    "contracts_expiring_soon": 1,
    "total_monthly_salary": 180000,
    "recent_employees": [
      {
        "id": 5,
        "prenom": "Fatima",
        "nom": "Hassan",
        "nationalite": "Djiboutienne",
        "type_emploi": "Ménage",
        "date_ajout": "2024-07-10T14:30:00Z"
      }
    ],
    "employer": {
      "id": 1,
      "prenom": "Jean",
      "nom": "Dupont",
      "email": "jean.dupont@email.com"
    }
  }
}
```

**Implémentation Flutter:**
```dart
// Widget de résumé rapide
Widget buildQuickSummary(DashboardSummary summary) {
  return Card(
    child: Padding(
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Résumé Rapide', style: Theme.of(context).textTheme.headline6),
          SizedBox(height: 16),
          
          // Métriques importantes
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildMetric('Contrats Actifs', summary.activeContracts.toString()),
              _buildMetric('En Attente', summary.pendingConfirmations.toString()),
              _buildMetric('Expire Bientôt', summary.contractsExpiringSoon.toString()),
            ],
          ),
          
          // Salaire total
          Container(
            margin: EdgeInsets.symmetric(vertical: 16),
            padding: EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.green.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                Icon(Icons.attach_money, color: Colors.green),
                SizedBox(width: 8),
                Text('Salaire Total Mensuel: ${summary.totalMonthlySalary} FDJ'),
              ],
            ),
          ),
          
          // Derniers employés ajoutés
          if (summary.recentEmployees.isNotEmpty) ...[
            Text('Derniers Ajouts:', style: Theme.of(context).textTheme.subtitle1),
            SizedBox(height: 8),
            ...summary.recentEmployees.map((employee) => 
              ListTile(
                leading: CircleAvatar(child: Text(employee.prenom[0])),
                title: Text('${employee.prenom} ${employee.nom}'),
                subtitle: Text(employee.typeEmploi ?? ''),
                trailing: Text(formatDate(employee.dateAjout)),
              )
            ),
          ],
        ],
      ),
    ),
  );
}
```

---

### 11. 📥 **Export Données Employés**

**Endpoint:** `GET /reports/employees`

**Usage mobile:** Génération et partage de rapports

**Paramètres Query:**
- `format`: json, csv, pdf, excel
- `date_from`: 2024-01-01
- `date_to`: 2024-12-31

**Exemple:** `GET /reports/employees?format=json&date_from=2024-01-01`

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Export généré avec succès",
  "data": {
    "format": "json",
    "total_records": 5,
    "date_from": "2024-01-01",
    "date_to": null,
    "generated_at": "2024-07-15T10:30:00Z",
    "employees": [
      {
        "employee_id": "000001",
        "prenom": "Marie",
        "nom": "Doe",
        "genre": "Femme",
        "date_naissance": "1990-05-15",
        "nationalite": "Française",
        "region": "Djibouti",
        "ville": "Djibouti",
        "quartier": "Plateau du Serpent",
        "date_arrivee": "2023-01-15",
        "etat_civil": "Célibataire",
        "is_active": true,
        "contrat_actuel": {
          "type_emploi": "Ménage",
          "salaire_mensuel": 45000,
          "date_debut": "2024-01-01",
          "est_actif": true
        },
        "nombre_contrats": 2,
        "total_confirmations": 6,
        "date_enregistrement": "2024-01-01T08:00:00Z"
      }
    ]
  }
}
```

**Implémentation Flutter:**
```dart
// Export et partage
Future<void> exportAndShare() async {
  // Afficher dialog de sélection format
  final format = await showFormatDialog();
  if (format == null) return;
  
  // Afficher loading
  showLoadingDialog();
  
  try {
    final exportData = await reportService.exportEmployees(
      format: format,
      dateFrom: selectedDateFrom,
      dateTo: selectedDateTo,
    );
    
    // Sauvegarder fichier localement
    final file = await saveExportFile(exportData, format);
    
    // Partager
    await Share.shareFiles([file.path], text: 'Export Employés SGRE');
    
  } catch (e) {
    showErrorDialog('Erreur lors de l\'export: $e');
  } finally {
    hideLoadingDialog();
  }
}
```

---

### 12. ⏳ **Confirmations en Attente**

**Endpoint:** `GET /monthly-confirmations/pending`

**Usage mobile:** Écran de confirmations mensuelles + notifications

**Réponse Succès (200):**
```json
{
  "success": true,
  "message": "Confirmations en attente récupérées avec succès",
  "data": {
    "pending_confirmations": [
      {
        "contract_id": 1,
        "employee": {
          "id": 1,
          "prenom": "Marie",
          "nom": "Doe",
          "nationalite": "Française",
          "photo_url": "https://api.sgre.dj/storage/employees/photos/photo_1.jpg"
        },
        "contract": {
          "type_emploi": "Ménage",
          "salaire_mensuel": 45000,
          "date_debut": "2024-01-01"
        },
        "confirmation_details": {
          "mois": 7,
          "annee": 2024,
          "mois_nom": "July 2024",
          "deadline": "2024-07-31T23:59:59Z"
        }
      }
    ],
    "total_pending": 2,
    "current_month": 7,
    "current_year": 2024,
    "month_name": "July 2024"
  }
}
```

**Implémentation Flutter:**
```dart
// Liste des confirmations en attente
Widget buildPendingConfirmations(List<PendingConfirmation> pending) {
  return ListView.builder(
    itemCount: pending.length,
    itemBuilder: (context, index) {
      final confirmation = pending[index];
      return Card(
        margin: EdgeInsets.all(8),
        child: ListTile(
          leading: CircleAvatar(
            backgroundImage: confirmation.employee.photoUrl != null
                ? NetworkImage(confirmation.employee.photoUrl!)
                : null,
            child: confirmation.employee.photoUrl == null
                ? Text(confirmation.employee.prenom[0])
                : null,
          ),
          title: Text('${confirmation.employee.prenom} ${confirmation.employee.nom}'),
          subtitle: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('${confirmation.contract.typeEmploi} - ${confirmation.contract.salaireMensuel} FDJ'),
              Text('Échéance: ${formatDate(confirmation.confirmationDetails.deadline)}'),
            ],
          ),
          trailing: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              IconButton(
                icon: Icon(Icons.check_circle, color: Colors.green),
                onPressed: () => confirmEmployee(confirmation),
              ),
              IconButton(
                icon: Icon(Icons.schedule, color: Colors.orange),
                onPressed: () => remindLater(confirmation),
              ),
            ],
          ),
        ),
      );
    },
  );
}
```

---

### 13. ✅ **Confirmation Directe Employé**

**Endpoint:** `POST /employees/{id}/monthly-confirmations`

**Usage mobile:** Bouton de confirmation rapide dans liste

**Requête:**
```json
{
  "mois": 7,
  "annee": 2024,
  "commentaire": "Employé présent et satisfaisant"
}
```

**Réponse Succès (201):**
```json
{
  "success": true,
  "message": "Employé confirmé avec succès",
  "data": {
    "confirmation": {
      "id": 25,
      "mois": 7,
      "annee": 2024,
      "mois_nom": "July 2024",
      "commentaire": "Employé présent et satisfaisant",
      "date_confirmation": "2024-07-15T10:30:00Z"
    },
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe",
      "nationalite": "Française"
    },
    "contract": {
      "id": 1,
      "type_emploi": "Ménage"
    }
  }
}
```

**Réponse Erreur (400):**
```json
{
  "success": false,
  "message": "Cet employé a déjà été confirmé pour ce mois"
}
```

**Implémentation Flutter:**
```dart
// Confirmation rapide avec feedback
Future<void> quickConfirmEmployee(int employeeId) async {
  try {
    // Afficher loading sur le bouton
    setConfirmationLoading(employeeId, true);
    
    final result = await confirmationService.confirmEmployee(
      employeeId: employeeId,
      month: DateTime.now().month,
      year: DateTime.now().year,
      comment: 'Confirmé depuis l\'application mobile',
    );
    
    // Feedback visuel
    showSuccessSnackbar('${result.employee.prenom} confirmé pour ce mois');
    
    // Mettre à jour la liste
    await refreshPendingConfirmations();
    
    // Animation de succès
    showConfirmationSuccessAnimation();
    
  } catch (e) {
    showErrorSnackbar('Erreur: ${e.toString()}');
  } finally {
    setConfirmationLoading(employeeId, false);
  }
}

// Widget bouton avec états
Widget buildConfirmButton(Employee employee) {
  final isLoading = confirmationLoadingStates[employee.id] ?? false;
  
  return ElevatedButton.icon(
    onPressed: isLoading ? null : () => quickConfirmEmployee(employee.id),
    icon: isLoading 
        ? SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(strokeWidth: 2),
          )
        : Icon(Icons.check),
    label: Text(isLoading ? 'Confirmation...' : 'Confirmer'),
    style: ElevatedButton.styleFrom(
      backgroundColor: Colors.green,
      foregroundColor: Colors.white,
    ),
  );
}
```

---

### 14. 📄 **Upload Documents Profil Employeur**

**Endpoint:** `POST /profile/documents`

**Usage mobile:** Ajout documents dans profil employeur

**Requête:**
```json
{
  "type_document": "piece_identite",
  "document": "data:application/pdf;base64,JVBERi0xLjQKJcOB...",
  "nom_document": "Carte_Identite.pdf"
}
```

**Types acceptés:**
- `piece_identite`
- `justificatif_domicile` 
- `autre`

**Réponse Succès (201):**
```json
{
  "success": true,
  "message": "Document uploadé avec succès",
  "data": {
    "document_url": "https://api.sgre.dj/storage/employers/documents/piece_identite_employer_1_1656789123.pdf",
    "type_document": "piece_identite",
    "uploaded_at": "2024-07-15T10:30:00Z"
  }
}
```

**Implémentation Flutter:**
```dart
// Sélection et upload de document
Future<void> uploadProfileDocument() async {
  // Sélectionner type de document
  final docType = await showDocumentTypeDialog();
  if (docType == null) return;
  
  try {
    // Sélectionner fichier
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    );
    
    if (result != null && result.files.single.path != null) {
      final file = File(result.files.single.path!);
      
      // Convertir en base64
      final bytes = await file.readAsBytes();
      final base64 = base64Encode(bytes);
      final mimeType = lookupMimeType(file.path) ?? 'application/pdf';
      final base64WithHeader = 'data:$mimeType;base64,$base64';
      
      // Upload
      showUploadProgress();
      
      await profileService.uploadDocument(
        type: docType,
        document: base64WithHeader,
        fileName: result.files.single.name,
      );
      
      showSuccessMessage('Document uploadé avec succès');
      await refreshProfile();
      
    }
  } catch (e) {
    showErrorMessage('Erreur upload: ${e.toString()}');
  } finally {
    hideUploadProgress();
  }
}

// Dialog sélection type
Future<String?> showDocumentTypeDialog() {
  return showDialog<String>(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Type de document'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            title: Text('Pièce d\'identité'),
            onTap: () => Navigator.pop(context, 'piece_identite'),
          ),
          ListTile(
            title: Text('Justificatif de domicile'),
            onTap: () => Navigator.pop(context, 'justificatif_domicile'),
          ),
          ListTile(
            title: Text('Autre document'),
            onTap: () => Navigator.pop(context, 'autre'),
          ),
        ],
      ),
    ),
  );
}
```

---

## 🔧 GESTION D'ERREURS COMMUNES

### **Codes de statut HTTP:**
- **200**: Succès
- **201**: Créé avec succès  
- **400**: Erreur de validation/logique métier
- **401**: Non authentifié (token invalide/expiré)
- **403**: Non autorisé
- **404**: Ressource non trouvée
- **422**: Erreur de validation des données
- **500**: Erreur serveur

### **Gestion Flutter:**
```dart
// Intercepteur global pour gestion d'erreurs
class ApiInterceptor extends Interceptor {
  @override
  void onError(DioError err, ErrorInterceptorHandler handler) {
    switch (err.response?.statusCode) {
      case 401:
        // Token expiré - rediriger vers login
        authService.logout();
        navigationService.pushAndRemoveUntil('/login');
        break;
        
      case 403:
        showErrorMessage('Accès non autorisé');
        break;
        
      case 404:
        showErrorMessage('Ressource non trouvée');
        break;
        
      case 422:
        // Erreurs de validation - afficher détails
        final errors = err.response?.data['errors'];
        showValidationErrors(errors);
        break;
        
      case 500:
        showErrorMessage('Erreur serveur. Veuillez réessayer.');
        break;
        
      default:
        showErrorMessage('Erreur réseau: ${err.message}');
    }
    
    super.onError(err, handler);
  }
}
```

---

## 🚀 GUIDE D'INTÉGRATION RAPIDE

### **1. Priorisation des endpoints:**

**Phase 1 - Essentiel:**
- ✅ Refresh token (expérience utilisateur)
- 📊 Dashboard stats/summary (écran principal)
- ⏳ Confirmations en attente (obligation légale)

**Phase 2 - Important:**
- 📸 Update photo employé (facilité d'usage)
- 🔍 Recherche employés (éviter doublons)
- 📜 Historique contrats (transparence)

**Phase 3 - Avancé:**
- 🔄 Ré-enregistrement (workflow complexe)
- ❌ Terminaison contrats (processus administratif)
- 📥 Export données (rapports)

### **2. Services Flutter recommandés:**

```dart
// Structure services
abstract class ApiService {
  late final Dio _dio;
  late final String _baseUrl;
  late final AuthService _authService;
}

class EmployeeService extends ApiService {
  Future<List<Employee>> searchEmployees(SearchCriteria criteria);
  Future<void> updatePhoto(int id, String base64Photo);
  Future<EmployeeContract> registerExisting(int id, ContractData contract);
  Future<List<Contract>> getContracts(int employeeId);
  Future<Contract> createContract(int employeeId, ContractData data);
}

class DashboardService extends ApiService {
  Future<DashboardStats> getStats();
  Future<DashboardSummary> getSummary();
  Future<ExportData> exportEmployees({String? format, DateTime? from, DateTime? to});
}

class ConfirmationService extends ApiService {
  Future<List<PendingConfirmation>> getPendingConfirmations();
  Future<ConfirmationResult> confirmEmployee(int employeeId, int month, int year, String? comment);
}
```

### **3. Modèles de données:**

```dart
// Modèles principaux
class DashboardStats {
  final OverviewStats overview;
  final Map<String, int> employeesByType;
  final Map<String, int> employeesByNationality;
  final List<EmployeeEvolution> employeeEvolution;
  final Map<String, double> averageSalaries;
  final List<ConfirmationStatistic> confirmationStatistics;
}

class PendingConfirmation {
  final int contractId;
  final Employee employee;
  final Contract contract;
  final ConfirmationDetails confirmationDetails;
}

class ExportData {
  final String format;
  final int totalRecords;
  final DateTime? dateFrom;
  final DateTime? dateTo;
  final List<EmployeeExport> employees;
}
```

**Cette documentation vous donne tout ce qu'il faut pour intégrer efficacement les nouveaux endpoints dans votre application mobile existante !**