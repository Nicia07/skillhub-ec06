# SkillHub - Plateforme collaborative

SkillHub est une application web moderne conçue pour connecter les formateurs et les apprenants. Ce projet met en avant une architecture où un backend en Laravel communique via une API REST sécurisée avec un frontend dynamique en React.


## Stack Technique

* **Backend :** Laravel 11 (PHP 8.2+), Eloquent ORM.
* **Base de données :** MySQL (Développement) / SQLite (Tests unitaires).
* **Authentification :** JSON Web Tokens (Tymon JWTAuth).
* **Frontend :** React.js, Axios, CSS3.
* **Documentation :** OpenAPI 3.0.3(Swagger/YAML).

---

## Points Clés du Développement

### 1. Interface Réactive : Système de Modale (React)
Le cœur de l'expérience utilisateur pour les formateurs repose sur le composant 'FormationModal.jsx', conçu pour une fluidité maximale :
* **Composant Hybride :** Une interface unique gère intelligemment la **création** (`POST`) et la **modification** (`PUT`) des formations.
* **Gestion des États :** Utilisation des hooks `useState` et `useEffect` pour pré-remplir instantanément le formulaire avec les données existantes (Titre, Catégorie, Prix, etc.) lors de l'édition.
* **Persistance du Token :** Récupération automatique du token JWT depuis le `localStorage` pour forger l'en-tête `Authorization: Bearer` requis par le backend.

### 2. Routage Stratégique et Sécurisé
Le routage dans `routes/api.php` est structuré pour garantir une sécurité totale entre les données publiques et privées :

| Méthode | Endpoint | Middleware | Rôle |
|---|---|---|---|
| **GET** | `/api/formations` | *Public* | Catalogue complet, consultable sans connexion. |
| **POST** | `/api/formations` | `auth:api` + `role:formateur` | Création d'une formation (liée au formateur connecté). |
| **PUT** | `/api/formations/{id}` | `auth:api` + `role:formateur` | Mise à jour d'une formation par son propriétaire. |
| **DELETE** | `/api/formations/{id}` | `auth:api` + `role:formateur` | Suppression d'une formation par son propriétaire. |
| **GET** | `/api/my-formations` | `auth:api` | Dashboard personnel filtré pour le formateur connecté. |
| **GET** | `/api/mes-inscriptions` | `auth:api` | Formations suivies/terminées de l'apprenant connecté. |
| **POST** | `/api/inscriptions` | `auth:api` + `role:apprenant` | Suivre une formation. |
| **PUT** | `/api/inscriptions/{id}/terminer` | `auth:api` + `role:apprenant` | Marquer une formation suivie comme terminée. |
| **DELETE** | `/api/inscriptions/{id}` | `auth:api` + `role:apprenant` | Se désinscrire d'une formation en cours. |
| **POST** | `/api/formations/{id}/signalements` | JWT requis | Signaler un problème sur une formation (motif + description optionnelle). |
| **GET** | `/api/formations/{id}/signalements` | JWT requis + propriétaire | Consulter les signalements reçus, réservé au formateur propriétaire de la formation. |

Le modèle `Formation` utilise les champs `title`, `description`, `price`, `duration` (heures), `level` (`beginner`/`intermediate`/`advanced`) et `user_id`, conformément au sujet EC04.

### 3. Gestion Avancée des Erreurs (UX/DX)
L'application est conçue pour communiquer activement avec l'utilisateur en cas de problème :
* **Interception 422 :** L'API valide strictement les données (title, description, price, duration, level obligatoires). En cas d'échec, le frontend React capture l'erreur 422 et affiche dynamiquement le message d'erreur natif de Laravel dans la modale, guidant l'utilisateur.
* **401 vs 403 :** un handler d'exception dédié (`bootstrap/app.php`) distingue un token JWT absent (401) d'un token présent mais invalide/expiré (403), conformément au sujet.
* **Évolution du Schéma (Migrations) :** Utilisation de migrations versionnées pour faire évoluer la base de données, garantissant la cohérence entre les environnements de dev et de test.

### 4. Signalement d'un Problème sur une Formation
Depuis la page détail d'une formation (`/Formation/:id`), tout utilisateur connecté peut signaler un problème via une modale volontairement simple (`ReportProblemModal.jsx`) :
* **Front-end :** un `<select>` obligatoire (`contenu_inapproprie` / `erreur_technique` / `autre`), boutons Envoyer/Annuler. L'envoi passe par Axios (`api.post`) sans rechargement de page ; le token JWT est injecté automatiquement depuis le `localStorage` par l'intercepteur global (`api.js`). En cas de succès, la modale se ferme et une confirmation simple s'affiche (`alert`).
* **Back-end :** table `signalements` (migration avec garde `Schema::hasTable`) contenant `formation_id`, `user_id`, `motif` (enum), `description` (texte, optionnel), `statut` (enum, défaut `en_attente`).
* **Sécurité de l'endpoint (`POST /api/formations/{id}/signalements`) :**
  * Protégé par middleware JWT (401 si token absent, 403 si invalide, cohérent avec le reste de l'API).
  * `formation_id` provient uniquement de l'URL, jamais du body ; `user_id` est déduit du token via `auth()->user()->id`, jamais envoyé par le client.
  * Vérifie l'existence de la formation avant d'enregistrer (404 sinon).
  * Empêche un même utilisateur de signaler deux fois la même formation (422 avec message explicite).
* **Consultation (`GET /api/formations/{id}/signalements`) :** réservée au formateur propriétaire de la formation (403 pour tout autre utilisateur authentifié).
* **Documentation :** les deux routes sont décrites dans `openapi.yaml` (description, paramètres path/header/body, exemples de payload et de réponses par code HTTP).

---

## Tests Unitaires

La fiabilité de l'API est garantie par une suite de tests fonctionnels (Feature Tests) exécutés via **PHPUnit**. Nous avons implémenté deux scénarios critiques pour sécuriser la plateforme :



#### **1. Test de Sécurité : Rejet sans token (401)**
* **Scénario :** Simulation d'une tentative de création de formation par un utilisateur non authentifié.
* **Vérification :** Le test affirme que le serveur renvoie un code **401 Unauthorized**.
* **Objectif :** Garantir qu'aucun utilisateur anonyme ne peut modifier les données de la plateforme.

#### **2. Test de Succès : Cycle complet de création (201)**
* **Scénario :** Création d'un "Formateur" via une Factory, génération d'un token JWT via `JWTAuth::fromUser()`, et envoi d'une requête valide incluant tous les champs métiers (y compris le prix).
* **Vérification :** * Le serveur doit répondre avec un code **201 Created**.
    * On vérifie avec `assertDatabaseHas` que la formation est physiquement présente en base de données.
* **Objectif :** Prouver que la chaîne Authentification -> Validation -> Insertion est 100% fonctionnelle.

`tests/Feature/SignalementTest.php` complète cette suite avec trois scénarios dédiés au signalement de formation :

#### **3. Test de Sécurité : Rejet sans token (401)**
* **Scénario :** Envoi d'un `POST /api/formations/{id}/signalements` sans header `Authorization`.
* **Vérification :** Le serveur renvoie **401**.

#### **4. Test de Succès : Signalement créé (201)**
* **Scénario :** Un "Apprenant" (Factory + token JWT) envoie un signalement valide (`motif` + `description`) sur une formation existante.
* **Vérification :** Réponse **201**, et `assertDatabaseHas` confirme l'enregistrement en base (`formation_id`, `user_id`, `motif`).

#### **5. Test de Règle Métier : Signalement en double (422)**
* **Scénario :** Le même utilisateur envoie un second signalement sur la même formation.
* **Vérification :** Le premier envoi renvoie 201, le second renvoie **422** — un utilisateur ne peut signaler qu'une seule fois la même formation.

---

## Usage de l'IA (Gemini)

### Test Unitaires
L'IA a servi de guide pour configurer l'environnement de test (SQLite en mémoire) et résoudre les conflits liés aux Factories.

**Exemple de prompt utilisé :**
"Peux-tu m'expliquer la logique pour structurer deux tests avec PHPUnit : le premier pour intercepter les accès non autorisés (401) et le second pour valider une création réussie (201) ? J'ai besoin de comprendre comment simuler un utilisateur connecté avec JWT et comment isoler ma base de données pour les tests sans toucher à ma production."

### Communication API avec Axios
L'IA a également assisté la mise en place de la communication asynchrone entre le frontend React et le backend Laravel.

**Exemple de prompt utilisé :**
"Aide-moi à utiliser Axios pour l'envoi des requêtes HTTP depuis mon frontend React vers Laravel. Je veux comprendre comment inclure proprement mon token JWT dans les en-têtes (headers) et comment intercepter les erreurs de réponse pour les afficher dans ma modale."
---
## Installation & Lancement

**Côté Backend (Laravel) :**
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan serve
