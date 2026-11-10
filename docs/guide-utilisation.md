# Guide d'utilisation — Profil Advanced Web

> **Objectif :** développer une application Web PHP avec séparation des responsabilités, DTO, validation, services, repository PDO, injection de dépendances, session et protection CSRF.

Ce guide décrit **les fichiers effectivement générés**, synchronisés avec le profil `variant/web-session-csrf` de `project_exemple` et le bootstrap léger `Application / RouterFactory`.

## 1. Préparer l'environnement

Pré requis : PHP 8.2+, Composer, MySQL ou MariaDB, extension `pdo_mysql`.

```bash
composer install
# Adapter DATABASE_HOST, DATABASE_PORT, DATABASE_NAME,
# DATABASE_USERNAME, DATABASE_PASSWORD dans .env
# Importer le fichier db.sql avec votre client MySQL
php -S 127.0.0.1:8000 -t public
```

Le générateur a créé `.env` et remplacé le nom de base dans `db.sql`. Ne jamais versionner `.env`.

Visiter `/`, `/register` puis `/login`. L'inscription crée un compte ; la connexion met l'utilisateur en session.

## 2. Parcours d'une requête POST

```text
POST /login (formulaire HTML + csrf_token)
      |
      v
public/index.php → Application
  WebBootstrap → EnvironmentLoader (dev/test/prod) + session → Container → RouterFactory
      |
      v
#[Route(..., grants: ['IS_GUEST'], middleware: [CsrfMiddleware::class])]
      |
      v
CsrfMiddleware -> validation du jeton de formulaire
      |
      v
LoginController -> LoginInput::fromArray($request->allPost())
      |
      v
LoginService -> Validator + UserRepository + PasswordHasher
      |
      v
UserSessionManager -> session PHP -> redirection
```

Un jeton CSRF absent ou invalide est rejeté **avant** l'exécution du contrôleur (HTTP 403).

## 3. Responsabilités et fichiers

| Composant | Rôle |
| --- | --- |
| `public/index.php` | Point d'entrée minimal : crée et lance `Application` |
| `src/Kernel/Application.php` | Orchestre le bootstrap, le conteneur et le dispatch |
| `src/Kernel/WebBootstrap.php` | Initialise les en-têtes et la session PHP | 
| `src/Kernel/EnvironmentLoader.php` | Sélectionne les fichiers locaux en dev/test, et les variables serveur uniquement en prod |
| `src/Kernel/RouterFactory.php` | Configure le routeur, les middlewares, les grants et la découverte des routes |
| `src/Security/GuestNavigation.php` | Conserve la dernière page GET autorisée en session ; redirection locale des utilisateurs connectés sur les routes invitées |
| `src/Kernel/WebErrorHandler.php` | Transforme les exceptions en réponses HTML échappées |
| `config/service.php` | Déclare la factory PDO et les paramètres du service d'upload |
| `src/Controller/` | Actions HTTP, rendu des templates et redirections |
| `src/DTO/` | Objets représentant les données d'entrée |
| `src/Service/` | Cas d'utilisation métier |
| `src/Repository/` | Requêtes SQL, hydratation des entités |
| `src/Entity/` | Objets métier |
| `src/Middleware/` | Vérifications transversales (ici CSRF) |
| `src/Security/` | Session, gestion CSRF et mots de passe |
| `template/` | Vues PHP/HTML |
| `tests/` | Vérifications autonomes du comportement |

**Règle pratique :** le contrôleur ne réalise pas de SQL, le repository ne génère pas de HTML et le service ne lit pas directement `$_POST`.

## 4. Déclarer une route

Une action déclarée avec un attribut est découverte automatiquement :

```php
use Mithridatem\Routing\Attribute\Route;

#[Route(path: '/catalogue', method: 'GET', grants: ['ROLE_USER'])]
public function catalogue(): mixed
{
    // Demander les données au service, puis rendre la vue.
}
```

Les `grants` permettent de demander un rôle. Dans ce profil, `AuthenticationAwareGrantChecker` reconnaît `IS_GUEST` (visiteur non connecté) et `IS_AUTHENTICATED` (utilisateur connecté), tandis que `ArrayGrantChecker` continue de vérifier les rôles présents dans `$_SESSION['user']['grant']`. Les rôles existants restent combinés en OU et la contrainte d'authentification s'ajoute aux rôles.

Les contrôleurs peuvent regrouper leurs routes avec `#[RouteGroup(prefix: '/login')]` et des méthodes `#[Route(path: '/', method: 'GET'|'POST')]`. Une route POST avec formulaire sensible doit être protégée par `CsrfMiddleware` **sur l'action POST uniquement**. Le middleware est générique : générer le token avec le chemin public de soumission (ex. `$this->csrf->generate('/catalogue')`) puis placer `CsrfMiddleware` sur cette route POST. Aucun registre de formulaires n'est à modifier. Voir `docs/route-groups-web.md`.

### Routes réservées aux invités

Les routes `/login` et `/register` (GET **et** POST) portent `grants: ['IS_GUEST']`. Une visite par un utilisateur déjà connecté provoque une `UnauthorizedException` dans le routeur ; l'application Web redirige ensuite en HTTP 303 vers la dernière page GET interne autorisée mémorisée en session ou vers `/` par défaut. Cette destination de repli est configurable dans `Application` via `GuestNavigation`. Si aucune destination sûre n'existe, la réponse 403 est conservée. Aucune session ni redirection n'est imposée par la librairie router. Voir [le guide détaillé](guest-navigation.md).

Pour une route accessible à toute personne connectée, utiliser `grants: ['IS_AUTHENTICATED']`. Les API JWT ne sont pas concernées par le mécanisme de navigation Web.

## 5. Exemple de parcours : inscription

1. `GET /register` affiche un formulaire contenant un jeton généré par `CsrfTokenManager`.
2. `POST /register` déclenche `CsrfMiddleware` qui vérifie ce jeton.
3. `RegisterController` construit `RegisterUserInput` avec `allPost()`.
4. `UserService` applique les validations et recherche les doublons via `UserRepository`.
5. Le repository utilise PDO pour enregistrer l'entité `User`.
6. Le contrôleur rend le résultat en HTML.

Ces étapes montrent clairement la différence entre **contrôle technique**, **orchestration HTTP**, **règles métier** et **persistance**.

## 6. Autowiring : où configurer une dépendance ?

Les classes concrètes sont résolues automatiquement lorsque le conteneur peut construire leurs paramètres.

`config/service.php` déclare par exemple la factory PDO :

```php
$container->factory(
    PDO::class,
    static fn (): PDO => Mysql::connectBdd()
);
```

Pour un paramètre scalaire, utiliser `configure()`, comme pour `UploadFileService`. Les services métiers ne doivent pas manipuler eux-mêmes le conteneur.

## Rendu des templates et composants

Dans le profil Web avancé, `AbstractController::render($template, $title, $data)` retourne désormais une **chaîne HTML**. Chaque fichier `template/template_<nom>.php` représente une page HTML complète (sans layout imposé). Les variables `$title` et `$data` sont disponibles directement dans la vue.

```php
public function home(): string
{
    return $this->render('home', 'Accueil', ['message' => 'Bienvenue']);
}
```

Pour intégrer un composant dans une page, utiliser **`echo` ou `<?= ... ?>`** : `renderComponent()` retourne aussi une chaîne et n'écrit plus directement dans la sortie.

```php
<h1><?= htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8') ?></h1>
<?= $this->renderComponent('error_validation', ['errors' => $data['errors'] ?? []]) ?>
```

Les deux méthodes utilisent un buffer interne commun, nettoyé en cas d'exception. Les noms de vues et composants doivent être des identifiants simples (lettres, chiffres, `_`, `-`) et ne doivent jamais être dérivés directement d'une entrée utilisateur. Un fichier absent déclenche une `RuntimeException`. Le point d'entrée Web affiche la chaîne renvoyée par le routeur. Il n'y a aucun changement pour les profils basic et API JWT.

Vérifier ce comportement avec `php tests/template_render_test.php` et `php tests/auth_form_render_test.php`.

## 7. Tests et vérifications

```bash
php tests/environment_loader_test.php
php tests/web_kernel_test.php
php tests/guest_navigation_test.php
php tests/login_input_test.php
php tests/register_user_input_test.php
php tests/csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/csrf_route_configuration_test.php
php tests/request_dto_mapping_test.php
php tests/form_submission_test.php
php tests/auth_form_render_test.php
php tests/user_session_manager_test.php
```

Tester également en navigateur avec une vraie base de données : formulaire valide, mauvais mot de passe, email déjà utilisé, POST sans jeton CSRF et session connectée.

## 8. Sécurité et limites

- En production : HTTPS, cookies sécurisés, limitation des tentatives de connexion, protection des secrets et gestion contrôlée des erreurs SQL.
- Toujours échapper les valeurs dynamiques dans les templates HTML.
- Ne pas modifier les rôles depuis des données envoyées par le navigateur.
- Ne pas désactiver le CSRF sous prétexte qu'un utilisateur est connecté.
- Le squelette est pédagogique et doit être complété par des tests d'intégration selon le projet.

**Pour les détails :** consulter `docs/csrf-middleware.md`, puis le code réel de `LoginController`, `CsrfMiddleware` et `UserService`.

## 9. Déploiement et environnement

`APP_ENV=dev` charge `.env` ; `APP_ENV=test` charge `.env.test`. En production, définir `APP_ENV=prod` dans l'hébergement et injecter directement les variables d'environnement : aucun fichier dotenv n'est lu. Le générateur crée uniquement un `.env` **local ignoré par Git**. Guide complet : `docs/environments.md`.

## Mise à jour des librairies auditées (novembre 2026)

Le profil déclare au minimum `mithridatem/router:^1.7.1`,
`mithridatem/autowiring:^1.4.2` et `mithridatem/validation:^1.2.1`.
Les versions correctives compatibles pourront être prises en charge par Composer.

`UserRepository` n'affiche plus les exceptions PDO dans la réponse HTTP :
les erreurs remontent au gestionnaire HTTP applicatif, qui produit une
réponse générique. `User::hydrate()` convertit les champs date SQL en
`DateTimeImmutable` et accepte une date `updated_at` nulle.

Les régressions de confidentialité SQL et d'hydratation se vérifient avec :

```bash
php tests/security/reproduce_repository_leak.php
```

Ces tests ne remplacent pas une validation avec une base MySQL réelle,
un serveur HTTP et des paramètres HTTPS de production.

## Convention PDO pour les repositories

Lorsque vous ajoutez une requête SQL avec paramètres :

- Utiliser des paramètres positionnels `?` (indices PDO à partir de 1).
- Préparer la requête, associer chaque valeur avec `bindValue($position, $value, PDO::PARAM_*)`, puis appeler `execute()` sans argument.
- Choisir explicitement `PDO::PARAM_INT`, `PDO::PARAM_STR`, `PDO::PARAM_BOOL` ou `PDO::PARAM_NULL` selon la valeur réellement transmise.
- Utiliser `bindParam()` uniquement si une liaison par référence est nécessaire (valeur de la variable susceptible de changer avant `execute()`).
- Conserver `query()` pour une requête statique sans entrée paramétrée.
- Garder les requêtes et leurs liaisons visibles dans le repository ; `AbstractRepository` reste réservé à la connexion PDO et aux méthodes abstraites communes, sans helper de binding.

Exemple :

```php
$stmt = $this->connect->prepare('SELECT * FROM user WHERE id_user = ?');
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();
```
