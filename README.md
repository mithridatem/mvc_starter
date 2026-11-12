# mvc_starter — web

> Référence autonome du profil `web` de [mvc_script_generator](https://github.com/mithridatem/mvc_script_generator). Ce dépôt sert à **valider** les évolutions avant leur synchronisation dans le générateur.

**Workflow :** évolution technique → validation ici (tests et review) → synchronisation du générateur → test d'un projet généré.

Profils disponibles dans les branches : [basic](https://github.com/mithridatem/mvc_starter/tree/feat/basic-reference), [Web session/CSRF](https://github.com/mithridatem/mvc_starter/tree/variant/web), [API JWT](https://github.com/mithridatem/mvc_starter/tree/variant/api-jwt).

**Ne pas synchroniser de logique métier vers le générateur.** Les exercices sur les livres et les cas d'utilisation IDOR restent dans `project_exemple`.

---

# MVC Advanced — Web : sessions PHP + protection CSRF

## Guide pas à pas

Après génération, consulter **[docs/guide-utilisation.md](docs/guide-utilisation.md)** pour comprendre le parcours des requêtes, le rôle des composants et ajouter une fonctionnalité pas à pas.

Ce profil de référence est indépendant de l'application métier `project_exemple`.

Il utilise `mithridatem/router`, `mithridatem/validation` et `mithridatem/autowiring`. Authentification HTML en session PHP, `grants` de routes et vérification CSRF dans le **middleware** (plus dans les contrôleurs).

## Environnements

`APP_ENV=dev` utilise `.env`, `APP_ENV=test` charge `.env.test` et **`APP_ENV=prod` n'utilise aucun fichier dotenv** : injecter les variables/secrets depuis le serveur ou le conteneur. Voir [docs/environments.md](docs/environments.md). Les routes Login/Register utilisent désormais `RouteGroup` et le CSRF générique sans registre de formulaires.

## Installer

```bash
composer install
# Configurer le fichier .env généré (notamment DATABASE_PASSWORD)
# Importer db.sql dans MySQL/MariaDB
php -S 127.0.0.1:8000 -t public
```

Parcours : `GET /login`, `POST /login`, `GET /register`, `POST /register`. Les requêtes POST sont protégées par `CsrfMiddleware`. Une soumission sans token valide provoque une réponse HTTP 403 avec une page HTML et un lien vers l'accueil. Le contrôleur ne traite que les DTO et les cas d'utilisation. Les rôles Web continuent de provenir de la session PHP : `AuthenticationAwareGrantChecker` délègue les rôles à `ArrayGrantChecker` et ajoute `IS_GUEST` / `IS_AUTHENTICATED`. Les routes `/login` et `/register` sont réservées aux invités en GET et POST ; un utilisateur connecté est redirigé vers la dernière page GET autorisée ou un repli local (`/` par défaut). Voir [docs/guest-navigation.md](docs/guest-navigation.md).

## Architecture

- `public/index.php` : point d'entrée minimal, lance `Application`.
- `src/Kernel/Application.php` : orchestration du démarrage.
- `src/Kernel/WebBootstrap.php` : chargement de l'environnement, en-têtes et session PHP.
- `src/Kernel/RouterFactory.php` : routeur, résolveurs, grants et découverte des routes.
- `src/Kernel/WebErrorHandler.php` : traduction des exceptions en statuts et en-têtes HTTP, avec rendu HTML de secours.
- `src/Controller/ErrorController.php` : choix du message et du lien de navigation selon le statut HTTP.
- `template/template_error.php` : page HTML générique pour les erreurs (sans exposition des détails techniques).
- `src/Middleware/CsrfMiddleware.php` : vérification des tokens avant exécution du contrôleur.
- `src/Middleware/ContainerMiddlewareResolver.php` : adaptation au conteneur autowiring.
- `src/Security/GuestNavigation.php` : mémorisation en session et redirection des utilisateurs déjà connectés.
- `src/Security/CsrfTokenManager.php` : génération et validation par session.
- `src/Controller` : traitements HTTP, templates HTML.
- `src/DTO` : entrées validées ; `src/Service` : métier ; `src/Repository` : accès PDO.
- `config/service.php` : fabrique PDO et configuration upload.

La correction d'hydratation de l'entité `User` et les placeholders SQL sont conservés.

## Bases de données (MySQL, PostgreSQL, SQLite)

Le profil Web utilise désormais `ConnectionInterface` et `ConnectionFactory` pour choisir son adaptateur PDO avec `DATABASE_DRIVER=mysql|pgsql|sqlite`. MySQL/MariaDB reste le choix par défaut. Les repositories conservent leur injection de `PDO`, sans ORM supplémentaire. Les schémas sont dans `db.sql` (MySQL), `database/postgresql.sql` et `database/sqlite.sql` ; les données ne sont pas migrées automatiquement.

Consulter [le guide des bases de données](docs/databases.md) pour la configuration des extensions PHP et l'initialisation des schémas.

## Pages d'erreur HTML

Le `WebErrorHandler` conserve la traduction des exceptions en statuts HTTP (403, 404, 405, 500), ainsi que l'en-tête `Allow` des erreurs 405. Il délègue directement le rendu à `ErrorController::error()` sans repasser par le routeur. Le template unique `template/template_error.php` contient un lien de retour à l'accueil (ou de connexion pour 401). Les détails des exceptions ne sont jamais affichés ; un HTML minimal prend le relais si le template échoue. Les redirections `IS_GUEST` restent gérées par `Application`.

## Validation avant synchronisation

La procédure de validation de cette branche, comprenant la CI multi-versions et la recette manuelle avec MySQL, est décrite dans [docs/validation-starter.md](docs/validation-starter.md).

## Tests

```bash
php tests/database_connection_test.php
php tests/environment_loader_test.php
php tests/web_kernel_test.php
php tests/guest_navigation_test.php
php tests/login_input_test.php
php tests/register_user_input_test.php
php tests/csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/csrf_route_configuration_test.php
php tests/form_submission_test.php
php tests/user_session_manager_test.php
php tests/auth_form_render_test.php
php tests/template_render_test.php
php tests/request_dto_mapping_test.php
```

Puis tester le parcours complet sur une base de données réelle. Aucun endpoint JWT n'est généré dans ce profil. Ne committer ni `.env` ni les identifiants.
