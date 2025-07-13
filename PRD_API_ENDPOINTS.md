# PRD - API Endpoints pour SGRE (Système de Gestion et de Régulation des Employés)

## 1. Vue d'ensemble du projet

### Contexte
SGRE est une application mobile Flutter de gestion d'employés domestiques à Djibouti. L'application permet aux employeurs d'enregistrer, gérer et suivre leurs employés domestiques avec des fonctionnalités de contrats, confirmations mensuelles, et gestion documentaire.

### Objectif du PRD
Ce document spécifie les endpoints API REST nécessaires côté backend pour supporter toutes les fonctionnalités de l'application mobile Flutter.

## 2. Architecture technique

### Base URL
```
https://api.sgre.dj/api/v1
```

### Authentification
- JWT Bearer Token dans le header `Authorization: Bearer {token}`
- Tokens avec expiration et refresh token

### Format des réponses
Toutes les réponses suivent ce format standard :
```json
{
  "success": true|false,
  "message": "Message descriptif",
  "data": {...}, // Données spécifiques à l'endpoint
  "errors": {...} // En cas d'erreur de validation
}
```

## 3. Endpoints par catégorie

### 3.1 Authentification

#### POST /auth/login
**Objectif :** Authentifier un utilisateur employeur
```json
// Request
{
  "email": "user@example.com",
  "password": "password123"
}

// Response
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "nom": "Dupont",
      "prenom": "Jean",
      "telephone": "+25377123456",
      "adresse": "Djibouti Ville"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600
  }
}
```

#### POST /auth/register
**Objectif :** Enregistrer un nouvel employeur
```json
// Request
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "user@example.com",
  "telephone": "+25377123456",
  "password": "password123",
  "password_confirmation": "password123",
  "adresse": "Djibouti Ville"
}
```

#### POST /auth/refresh
**Objectif :** Renouveler le token d'accès
```json
// Request
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

#### POST /auth/logout
**Objectif :** Déconnecter l'utilisateur et invalider le token

### 3.2 Gestion des employés

#### GET /employees
**Objectif :** Récupérer la liste des employés de l'employeur connecté
```json
// Response
{
  "success": true,
  "data": {
    "employees": [
      {
        "id": 1,
        "prenom": "Marie",
        "nom": "Doe",
        "genre": "Femme",
        "date_naissance": "1990-05-15",
        "nationalite_id": 1,
        "nationalite": "Française",
        "region": "Djibouti",
        "ville": "Djibouti",
        "quartier": "Plateau du Serpent",
        "adresse_complete": "Rue de la République",
        "date_arrivee": "2023-01-15",
        "etat_civil": "Célibataire",
        "photo_url": "https://api.sgre.dj/uploads/employees/photo_123.jpg",
        "active_contract": {
          "id": 1,
          "type_emploi": "Femme de ménage",
          "salaire_mensuel": 45000,
          "date_debut": "2023-02-01",
          "date_fin": null,
          "est_actif": true
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 5
    }
  }
}
```

#### GET /employees/{id}
**Objectif :** Récupérer les détails d'un employé spécifique
```json
// Response
{
  "success": true,
  "data": {
    "employee": {
      "id": 1,
      "prenom": "Marie",
      "nom": "Doe",
      // ... tous les champs détaillés
      "contracts": [
        {
          "id": 1,
          "type_emploi": "Femme de ménage",
          "salaire_mensuel": 45000,
          "date_debut": "2023-02-01",
          "date_fin": null,
          "est_actif": true,
          "motif_fin": null
        }
      ],
      "monthly_confirmations": [
        {
          "id": 1,
          "mois": 3,
          "annee": 2024,
          "date_confirmation": "2024-03-15T10:30:00Z",
          "confirme_par": "Jean Dupont"
        }
      ]
    }
  }
}
```

#### POST /employees
**Objectif :** Créer un nouvel employé avec contrat
```json
// Request
{
  "prenom": "Marie",
  "nom": "Doe",
  "genre": "Femme",
  "date_naissance": "1990-05-15",
  "nationalite_id": 1,
  "region": "Djibouti",
  "ville": "Djibouti",
  "quartier": "Plateau du Serpent",
  "adresse_complete": "Rue de la République",
  "date_arrivee": "2023-01-15",
  "etat_civil": "Célibataire",
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ...",
  "piece_identite": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ...",
  "passeport": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ...", // optionnel
  "contract": {
    "type_emploi": "Femme de ménage",
    "salaire_mensuel": 45000,
    "date_debut": "2023-02-01"
  }
}
```

#### PUT /employees/{id}
**Objectif :** Mettre à jour les informations d'un employé
```json
// Request (mêmes champs que POST mais tous optionnels)
{
  "prenom": "Marie-Claire",
  "telephone": "+25377654321"
}
```

#### PUT /employees/{id}/photo
**Objectif :** Mettre à jour uniquement la photo d'un employé
```json
// Request
{
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ..."
}

// Response
{
  "success": true,
  "message": "Photo mise à jour avec succès",
  "data": {
    "employee": {
      "id": 1,
      "photo_url": "https://api.sgre.dj/uploads/employees/photo_456.jpg"
    }
  }
}
```

#### POST /employees/search
**Objectif :** Rechercher un employé existant pour réenregistrement
```json
// Request
{
  "employee_id": "EMP001", // optionnel
  "prenom": "Marie", // optionnel
  "nom": "Doe", // optionnel
  "telephone": "+25377123456", // optionnel
  "date_naissance": "1990-05-15" // optionnel
}

// Response
{
  "success": true,
  "data": {
    "employees": [
      {
        "id": 1,
        "prenom": "Marie",
        "nom": "Doe",
        "date_naissance": "1990-05-15",
        "telephone": "+25377123456",
        "nationalite": "Française",
        "current_employer": "Autre employeur",
        "can_register": true
      }
    ]
  }
}
```

#### POST /employees/{id}/register-existing
**Objectif :** Enregistrer un employé existant avec un nouvel employeur
```json
// Request
{
  "contract": {
    "type_emploi": "Femme de ménage",
    "salaire_mensuel": 45000,
    "date_debut": "2024-01-01"
  }
}
```

### 3.3 Gestion des contrats

#### GET /employees/{id}/contracts
**Objectif :** Récupérer l'historique des contrats d'un employé

#### POST /employees/{id}/contracts
**Objectif :** Créer un nouveau contrat pour un employé existant

#### PUT /contracts/{id}/terminate
**Objectif :** Terminer un contrat actif
```json
// Request
{
  "date_fin": "2024-03-31",
  "motif": "Fin de contrat à l'amiable"
}
```

### 3.4 Confirmations mensuelles

#### POST /employees/{id}/monthly-confirmations
**Objectif :** Confirmer qu'un employé travaille toujours pour l'employeur
```json
// Request
{
  "mois": 3,
  "annee": 2024,
  "commentaire": "Employé présent et satisfaisant"
}
```

#### GET /employees/{id}/monthly-confirmations
**Objectif :** Récupérer l'historique des confirmations mensuelles

#### GET /monthly-confirmations/pending
**Objectif :** Récupérer les confirmations mensuelles en attente

### 3.5 Nationalités

#### GET /nationalities
**Objectif :** Récupérer la liste des nationalités disponibles
```json
// Response
{
  "success": true,
  "data": {
    "nationalities": [
      {
        "id": 1,
        "nom": "Française",
        "code": "FR"
      },
      {
        "id": 2,
        "nom": "Djiboutienne",
        "code": "DJ"
      }
    ]
  }
}
```

### 3.6 Documents et fichiers

#### POST /documents/upload
**Objectif :** Upload de documents (alternative à l'envoi en Base64)
```json
// Request (multipart/form-data)
{
  "file": File,
  "type": "photo|piece_identite|passeport",
  "employee_id": 1
}

// Response
{
  "success": true,
  "data": {
    "file_url": "https://api.sgre.dj/uploads/documents/doc_123.jpg",
    "file_id": "doc_123"
  }
}
```

### 3.7 Statistiques et rapports

#### GET /dashboard/stats
**Objectif :** Récupérer les statistiques de l'employeur
```json
// Response
{
  "success": true,
  "data": {
    "total_employees": 5,
    "active_contracts": 4,
    "pending_confirmations": 2,
    "expired_contracts": 1,
    "employees_by_type": {
      "Femme de ménage": 3,
      "Garde d'enfants": 1,
      "Cuisinière": 1
    }
  }
}
```

#### GET /reports/employees
**Objectif :** Export des données d'employés
**Paramètres :** `format=csv|pdf|excel`, `date_from`, `date_to`

### 3.8 Profil employeur

#### GET /profile
**Objectif :** Récupérer le profil de l'employeur connecté

#### PUT /profile
**Objectif :** Mettre à jour le profil de l'employeur

#### POST /profile/documents
**Objectif :** Upload des documents de l'employeur (carte d'identité, etc.)

## 4. Codes d'erreur standardisés

- **200** : Succès
- **201** : Créé avec succès
- **400** : Erreur de validation
- **401** : Non authentifié
- **403** : Non autorisé
- **404** : Ressource non trouvée
- **422** : Erreur de validation des données
- **429** : Trop de requêtes
- **500** : Erreur serveur

## 5. Sécurité et validation

### Validation des données
- Tous les champs requis doivent être validés
- Validation des formats (email, téléphone, dates)
- Taille maximale des fichiers : 5MB
- Types de fichiers autorisés : JPG, PNG, PDF

### Sécurité
- Rate limiting : 100 requêtes/minute par utilisateur
- Validation des tokens JWT
- Sanitisation des données d'entrée
- Logs d'audit pour toutes les actions

## 6. Performance

### Pagination
- Limite par défaut : 20 éléments
- Limite maximale : 100 éléments

### Cache
- Cache des nationalités (24h)
- Cache des données de profil (1h)

## 7. Environnements

### Développement
- Base URL : `https://api-dev.sgre.dj/api/v1`

### Production
- Base URL : `https://api.sgre.dj/api/v1`

## 8. Documentation API

Une documentation Swagger/OpenAPI doit être générée automatiquement et accessible à :
- Dev : `https://api-dev.sgre.dj/docs`
- Prod : `https://api.sgre.dj/docs`

## 9. Tests requis

- Tests unitaires pour tous les endpoints
- Tests d'intégration
- Tests de performance
- Tests de sécurité

---

**Note :** Ce PRD doit être utilisé comme référence complète pour l'implémentation du backend API. Tous les endpoints listés sont nécessaires pour le bon fonctionnement de l'application mobile Flutter SGRE.