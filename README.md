# mvc_starter — api-jwt

> Référence autonome du profil `api-jwt` de [mvc_script_generator](https://github.com/mithridatem/mvc_script_generator). Ce dépôt sert à **valider** les évolutions avant leur synchronisation dans le générateur.

**Workflow :** évolution technique → validation ici (tests et review) → synchronisation du générateur → test d'un projet généré.

Profils disponibles dans les branches : [basic](https://github.com/mithridatem/mvc_starter/tree/feat/basic-reference), [Web session/CSRF](https://github.com/mithridatem/mvc_starter/tree/variant/web), [API JWT](https://github.com/mithridatem/mvc_starter/tree/variant/api-jwt).

**Ne pas synchroniser de logique métier vers le générateur.** Les exercices sur les livres et les cas d'utilisation IDOR restent dans `project_exemple`.

---

# MVC API JWT — Routing + Validation + Autowiring + Firebase JWT

## Guide pas à pas

Après génération, consulter **[docs/guide-utilisation.md](docs/guide-utilisation.md)** pour comprendre le parcours des requêtes, le rôle des composants et ajouter une fonctionnalité pas à pas.

Profil de référence autonome, initialement dérivé de `project_exemple` et désormais indépendant de son domaine métier. **API REST uniquement** : pas de session PHP, pas de vues HTML, pas de CSRF cookie. Les routes utilisent toujours `grants`.

## Environnements

Dans ce dépôt de référence, copier `.env.example` dans `.env` et générer un `JWT_SECRET` aléatoire ; `.env` est ignoré par Git. Le générateur automatise cette étape pour les projets distribués. En production, définir `APP_ENV=prod` dans l'hébergement et injecter les secrets directement (pas de `.env.prod`). `JWT_TTL_SECONDS=900` est personnalisable. Voir [docs/environments.md](docs/environments.md).

## Installation

```sh
composer install
# Vérifier les paramètres DATABASE_* et JWT_* dans .env
# Importer db.sql dans MySQL/MariaDB
php -S 127.0.0.1:8000 -t public
```

Le générateur crée un `JWT_SECRET` aléatoire avec `random_bytes(32)` dans le fichier `.env`, ignoré par Git. Ne jamais committer `.env`. Le JWT d'accès a une durée configurable (15 min par défaut), sans refresh token ni révocation dans ce POC.

## Requêtes

```sh
curl -X POST http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.test","password":"mot_de_passe"}'

curl http://127.0.0.1:8000/api/profile \
  -H 'Authorization: Bearer VOTRE_TOKEN'
```

- `POST /api/login` : valide le JSON via `LoginInput` et `LoginService` et retourne un access token.
- `GET /api/profile` : authentification par `JwtMiddleware`, contrôle `ROLE_USER` via `ContextGrantChecker`.
- HTTP 401 pour token absent/invalide, 403 pour droits insuffisants.
- Aucune dépendance JWT ajoutée à `router-librairie`.

## Architecture

- `public/index.php` : front controller minimal.
- `src/Kernel/Application.php` : orchestration du démarrage.
- `src/Kernel/ApiBootstrap.php` : en-têtes JSON ; `src/Kernel/EnvironmentLoader.php` : sélection dev/test/prod.
- `src/Kernel/RouterFactory.php` : configuration du routeur, du middleware d'authentification et des grants.
- `src/Kernel/ApiErrorHandler.php` : erreurs HTTP rendues en JSON.

`Controller/Api` : endpoints JSON ; `Security/Jwt` : émission et validation ; `Security/Authentication` : identité pendant la requête ; `Middleware` : contrôle transversal ; `Repository` : PDO ; `Service` : métier.

## Validation avant synchronisation

Voir [docs/validation-starter.md](docs/validation-starter.md) pour la matrice CI, les tests adversariaux JWT et la recette manuelle MySQL.

## Vérification

```sh
php tests/environment_loader_test.php
php tests/api_kernel_test.php
php tests/login_input_test.php
php tests/jwt_api_routes_test.php
php tests/jwt_authentication_test.php
```

Voir `docs/jwt-api.md` pour les limites de sécurité. En production : HTTPS, protection contre la force brute, politique de révocation/rotation et suivi des autorisations.

Les routes sont regroupées avec `#[RouteGroup]` : `AuthController` reste public, tandis que `ProfileController` hérite de `JwtMiddleware` et de `ROLE_USER` au niveau de sa classe.
