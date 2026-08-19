# SkillHub — EC06 : CI/CD, Docker et authentification forte SSO

SkillHub est une plateforme de formation en ligne. Ce dépôt contient la version EC06
du projet : un backend Laravel (API REST), un frontend React, et un nouveau
microservice Spring Boot dédié à l'authentification forte (SSO + Master Key),
le tout conteneurisé et livré via une chaîne CI/CD GitHub Actions analysée par
SonarCloud.

## 1. Architecture

```
                         ┌────────────────────────┐
                         │   Frontend React        │
                         │   (Skillhub/)            │
                         └───────────┬──────────────┘
                                     │ HTTPS / JSON
                                     ▼
┌────────────────────────────────────────────────────────────┐
│                    Backend Laravel (skillhub-back)           │
│  - Catalogue de formations, inscriptions, signalements       │
│  - Auth "métier" existante : JWT interne (Tymon JWTAuth)      │
│  - Auth forte SSO : POST /api/sso/login, GET /api/sso/profile │
└───────────┬───────────────────────────────────┬──────────────┘
            │ MySQL (skillhub_ec06)              │ API REST (X-Master-Key,
            ▼                                    │ puis Authorization: Bearer)
     ┌─────────────┐                             ▼
     │   MySQL     │                 ┌───────────────────────────┐
     └─────────────┘                 │ Spring Boot SSO (skillhub-sso)│
                                      │ POST /api/auth/login          │
                                      │ GET  /api/auth/validate       │
                                      └───────────┬────────────────────┘
                                                  │ JDBC
                                                  ▼
                                          ┌─────────────┐
                                          │ PostgreSQL  │
                                          │ (H2 en local)│
                                          └─────────────┘
```

Chaque service possède sa propre base de données et son propre cycle de vie
(build, tests, image Docker) : c'est une architecture microservices, où
l'authentification forte est **déléguée** par Laravel au microservice Spring
Boot plutôt que réimplémentée.

## 2. Authentification forte SSO (Master Key + JWT)

Le microservice `skillhub-sso` expose deux routes :

| Méthode | Route | Rôle |
|---|---|---|
| POST | `/api/auth/login` | Authentifie un utilisateur et retourne un JWT signé |
| GET | `/api/auth/validate` | Valide un JWT (header `Authorization: Bearer <token>`) |

`/api/auth/login` exige en plus un header `X-Master-Key`, un secret partagé
connu uniquement des clients de confiance (ici, le backend Laravel). C'est la
couche d'**authentification forte** : sans Master Key valide, la requête est
rejetée (403) avant même que le mot de passe utilisateur soit vérifié.

**Intégration côté Laravel :**

1. `POST /api/sso/login` (`SsoAuthController`) reçoit `email`/`password` du
   client, ajoute le header `X-Master-Key` (lu depuis `services.sso.master_key`,
   jamais exposé au client) et relaie la requête au microservice. Le JWT reçu
   est retourné tel quel au client.
2. Le client renvoie ensuite ce JWT dans `Authorization: Bearer <token>` sur
   les requêtes suivantes.
3. `GET /api/sso/profile` est protégée par le middleware `sso`
   (`App\Http\Middleware\SsoAuthenticate`, alias `sso` dans `bootstrap/app.php`) :
   il appelle `GET /api/auth/validate` sur le microservice à chaque requête.
   Si le microservice répond `valid: true`, la requête continue ; sinon, 401.

Flux complet : `POST /api/sso/login` → JWT → `GET /api/sso/profile` avec
`Authorization: Bearer <token>` → 200.

*(Cette route SSO est un ajout démonstratif à côté de l'authentification JWT
interne existante — utilisée par le catalogue de formations, les inscriptions,
etc. — qui reste inchangée pour ne pas casser les fonctionnalités déjà
livrées.)*

## 3. Règle métier : limite de 5 inscriptions actives

`POST /api/inscriptions` (`InscriptionController::store`) compte désormais les
inscriptions au statut `en cours` de l'apprenant avant de créer la nouvelle
inscription. Au-delà de 5, l'API retourne **HTTP 400** avec un message
explicite. Une formation marquée `terminée` libère une place. Voir
`tests/Feature/InscriptionLimiteTest.php`.

## 4. Lancer le projet

### Avec Docker (recommandé)

```bash
cp .env.example .env
# éditer .env : SSO_MASTER_KEY, SSO_JWT_SECRET, JWT_SECRET, DB_* si besoin
docker compose up --build
```

- Laravel : http://localhost:8000
- Spring Boot SSO : http://localhost:8081 (health : `/actuator/health`)
- MySQL et PostgreSQL démarrent avec leurs volumes dédiés.

`docker compose up --build` construit puis démarre les 4 services
(`mysql`, `sso-db`, `skillhub-sso`, `laravel`) sur un réseau Docker commun
(`skillhub-net`). Le conteneur Laravel attend que MySQL soit prêt
(healthcheck) avant de lancer les migrations (`docker-entrypoint.sh`).

### En local, sans Docker

**Backend Laravel :**
```bash
cd skillhub-back
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan serve
```

**Microservice Spring Boot SSO :**
```bash
cd skillhub-sso
mvn spring-boot:run
# démarre sur http://localhost:8081, profil "local" (H2 fichier)
```

**Frontend React :**
```bash
cd Skillhub
npm install
npm run dev
```

### Tester l'authentification SSO manuellement

```bash
curl -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Master-Key: change-me-master-key" \
  -d '{"email":"formateur@skillhub.test","password":"Formateur123!"}'
```

(Utilisateurs de démonstration créés automatiquement au premier démarrage :
`formateur@skillhub.test` / `Formateur123!` et `apprenant@skillhub.test` /
`Apprenant123!`, voir `SkillhubSsoApplication.seedUsers`.)

## 5. Variables d'environnement

Deux fichiers `.env.example` sont fournis :

- **`.env.example`** (racine) : variables utilisées par `docker-compose.yml`
  pour orchestrer les deux services (identifiants MySQL/PostgreSQL, Master
  Key, secret JWT du SSO...).
- **`skillhub-back/.env.example`** : configuration complète de Laravel pour un
  usage local hors Docker (inclut aussi `SSO_BASE_URL`, `SSO_MASTER_KEY`).

Aucun secret réel n'est committé : les valeurs par défaut sont des
placeholders explicitement nommés `change-me-...`.

## 6. Pipeline CI/CD (GitHub Actions)

`.github/workflows/ci.yml` se déclenche sur chaque push vers `dev` et `main` :

1. **`laravel`** : `composer install`, lint (`vendor/bin/pint --test`, basé
   sur PHP-CS-Fixer), tests (`php artisan test`), puis analyse SonarCloud du
   projet `skillhub-back`.
2. **`spring-boot`** : `mvn install`, `mvn test`, puis analyse SonarCloud du
   projet `skillhub-sso`.
3. **`build-and-push`** (seulement après succès des deux jobs précédents) :
   construit les images Docker `skillhub-back` et `skillhub-sso`, les tague
   avec le SHA du commit et le nom de la branche, puis les pousse sur
   GitHub Container Registry (`ghcr.io`).

### Secrets à configurer sur GitHub (Settings → Secrets and variables → Actions)

| Secret | Contenu |
|---|---|
| `SONAR_TOKEN` | Token généré sur sonarcloud.io (My Account → Security) |
| `REGISTRY_USER` | Nom d'utilisateur GitHub (propriétaire du package ghcr.io) |
| `REGISTRY_TOKEN` | Personal Access Token avec le scope `write:packages` |

Aucun identifiant n'apparaît en clair dans le dépôt : `sonar-project.properties`
(skillhub-back) et `pom.xml` (skillhub-sso, section `<properties>`) contiennent
uniquement `sonar.organization`/`sonar.projectKey`, **à compléter** avec les
valeurs réelles une fois les deux projets créés sur SonarCloud.

## 7. Qualité de code (SonarCloud)

`sonar-project.properties` (skillhub-back) et les propriétés `sonar.*` du
`pom.xml` (skillhub-sso) analysent chacun leur projet séparément. Après
la première exécution réussie du pipeline sur `dev`/`main` :

1. Récupérer le rapport SonarCloud **avant** l'ajout de la feature
   `limite-inscriptions` (analyse de `main`) et **après** (analyse de la
   branche `feature/limite-inscriptions` / `dev`), et les capturer en
   annexe du rendu.
2. Compléter ci-dessous le plan d'action des améliorations relevées par
   SonarCloud (bugs, code smells, duplications, couverture) — **sans modifier
   le code**, uniquement la liste des actions à mener :

   - _(à compléter après la première analyse réelle)_

## 8. Structure du dépôt

```
.
├── .github/workflows/ci.yml     # Pipeline CI/CD
├── docker-compose.yml           # Orchestration Laravel + Spring Boot + BDD
├── .env.example                 # Variables d'environnement (docker-compose)
├── skillhub-back/                # API Laravel (PHP 8.3)
│   ├── Dockerfile
│   ├── docker-entrypoint.sh
│   ├── sonar-project.properties
│   └── .env.example
├── skillhub-sso/                 # Microservice Spring Boot SSO (Java 17)
│   ├── Dockerfile
│   └── pom.xml
├── Skillhub/                     # Frontend React
└── openapi.yaml                  # Documentation API (Laravel)
```
