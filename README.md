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
│  - /api/register, /api/login : identifiants verifies par le SSO│
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

Le microservice `skillhub-sso` expose en plus `POST /api/auth/register`
(même protection Master Key), qui crée le compte credential (mot de passe
hashé en BCrypt côté SSO) et renvoie un JWT ; **409** si l'email existe déjà.

**Intégration côté Laravel — deux niveaux :**

1. **L'écran de connexion/inscription réel de React** (`AuthModal.jsx`)
   continue d'appeler `POST /api/register` et `POST /api/login` sur Laravel
   sans aucun changement côté frontend. En interne, `AuthController` ne
   vérifie plus rien localement :
   - `register()` appelle d'abord `POST /api/auth/register` sur le SSO
     (Master Key + email/password/role). Ce n'est **qu'en cas de succès**
     que Laravel crée la ligne `users` locale (données métier : rôle,
     pseudo, clés étrangères formations/inscriptions/signalements). Si le
     SSO refuse (409) ou est injoignable (503), aucun utilisateur Laravel
     n'est créé.
   - `login()` appelle `POST /api/auth/login` sur le SSO pour valider les
     identifiants (`JWTAuth::attempt` local a été supprimé). Si le SSO
     confirme, Laravel retrouve l'utilisateur local par email et émet son
     propre JWT (Tymon) — pour ne rien casser des routes déjà protégées
     par ce système (formations, inscriptions, signalements).
   - Résultat : le mot de passe n'est **jamais vérifié par Laravel** — la
     preuve d'identité vient entièrement du microservice.
2. **Route de démonstration dédiée**, en plus du flux ci-dessus :
   `POST /api/sso/login` (`SsoAuthController`) relaie directement le JWT
   émis par le SSO, et `GET /api/sso/profile` (middleware `sso` →
   `App\Http\Middleware\SsoAuthenticate`) valide ce JWT à chaque requête en
   appelant `GET /api/auth/validate` sur le microservice — flux complet :
   `POST /api/sso/login` → JWT → `GET /api/sso/profile` avec
   `Authorization: Bearer <token>` → 200.

> ⚠️ En développement local hors Docker, utiliser `SSO_BASE_URL=http://127.0.0.1:8081`
> et non `http://localhost:8081` : sur certaines machines Windows, la résolution
> IPv6 (`::1`) de `localhost` n'aboutit pas et fait attendre le timeout complet
> (5s) avant l'échec — voir `skillhub-back/.env.example`.

## 3. Règle métier : désinscription automatique après 30 jours d'inactivité

Un apprenant inactif depuis plus de 30 jours sur une formation qu'il suit est
automatiquement désinscrit.

- **Champ `last_activity_at`** (`DATETIME`, nullable) ajouté à la table
  `inscriptions` (migration `add_last_activity_at_to_inscriptions_table`,
  gardée par `Schema::hasColumn` pour rester idempotente si le champ existe déjà).
- **Middleware `App\Http\Middleware\TouchInscriptionActivity`** (alias `touch.activity`) :
  à chaque fois qu'un apprenant connecté consulte le détail d'une formation
  qu'il suit (`GET /api/formations/{id}`), `last_activity_at` est mis à jour
  sur son inscription correspondante.
- **Commande Artisan `php artisan app:desinscription-inactivite`**
  (`App\Console\Commands\DesinscriptionInactiviteCommand`) : parcourt les
  inscriptions au statut `en cours` dont `last_activity_at` est `NULL` ou
  antérieur à 30 jours, les supprime, puis affiche/loggue le nombre de
  désinscriptions effectuées (`Log::info`, canal par défaut).
- **Tests** : `tests/Feature/DesinscriptionInactiviteTest.php` couvre une
  inscription inactive depuis 31 jours (désinscrite), une inscription active
  depuis 5 jours (conservée), et une inscription sans activité enregistrée
  (désinscrite).

Pour exécuter la commande manuellement (ou via une tâche planifiée/cron) :
```bash
php artisan app:desinscription-inactivite
```

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
