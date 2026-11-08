# Guide d'utilisation — Profil API JWT

> **Objectif :** créer une API REST PHP renvoyant du JSON, avec authentification Bearer JWT, contrôle des rôles, séparation Controller / Service / Repository et aucun état de session PHP.

Ce guide décrit **le squelette actuel**, synchronisé avec `project_exemple:variant/api-jwt`. Le bootstrap léger `Application / ApiBootstrap / RouterFactory / ApiErrorHandler` est intégré au projet généré.

## 1. Installation

Prérequis : PHP 8.2+, Composer, MySQL ou MariaDB, extensions PDO adaptées.

```bash
composer install
# Vérifier les variables DATABASE_* dans .env
# Créer les tables en important db.sql
php -S 127.0.0.1:8000 -t public
```

Le générateur crée `.env` et un **secret JWT aléatoire distinct pour chaque projet**, dans la variable `JWT_SECRET`. Ne jamais exposer ce secret ni committer `.env`.

Il faut disposer d'un utilisateur en base avant d'appeler `/api/login`. Ce squelette ne comporte **pas de route d'inscription**. Pour un premier essai, importer ou créer un utilisateur de test en utilisant un mot de passe haché avec `password_hash()`, jamais un mot de passe en clair.

## 2. Parcours d'une requête API

```text
POST /api/login (JSON)
  -> AuthController -> LoginInput -> LoginService -> UserRepository
  -> JwtTokenManager -> JSON { access_token, token_type, expires_in }

GET /api/profile (Authorization: Bearer <token>)
  -> RequestAuthenticationMiddleware (réinitialisation du contexte)
  -> JwtMiddleware (validation JWT)
  -> ContextGrantChecker (ROLE_USER)
  -> ProfileController -> JSON { id, email, roles }
```

L'API ne démarre **aucune session PHP** et n'utilise pas de CSRF basé sur les cookies. Les rôles proviennent uniquement d'un token correctement vérifié, pas de `$_SESSION`.

## 3. Effectuer les appels HTTP

**Authentification :**

```bash
curl -i -X POST http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"utilisateur@example.test","password":"mot-de-passe"}'
```

Si les identifiants sont corrects, le serveur répond avec un `access_token`, un `token_type` Bearer et un `expires_in` configuré par `JWT_TTL_SECONDS` (900 secondes par défaut).

**Profil protégé :**

```bash
curl -i http://127.0.0.1:8000/api/profile \
  -H 'Authorization: Bearer COLLER_LE_TOKEN_ICI'
```

Le client doit transmettre l'en-tête `Authorization` à chaque appel protégé.

## 4. Architecture et responsabilités

| Composant | Rôle |
| --- | --- |
| `public/index.php` | Point d'entrée minimal : lance `Application` |
| `src/Kernel/Application.php` | Orchestre l'initialisation du conteneur, du routeur et le dispatch |
| `src/Kernel/ApiBootstrap.php` | Initialise les en-têtes JSON | 
| `src/Kernel/EnvironmentLoader.php` | Sélectionne les fichiers locaux en dev/test, et les variables serveur uniquement en prod |
| `src/Kernel/RouterFactory.php` | Configure le pipeline JWT, les grants et la découverte des routes |
| `src/Kernel/ApiErrorHandler.php` | Convertit les exceptions en réponses JSON HTTP sans divulguer les erreurs internes |
| `config/service.php` | Configuration du conteneur, fabrique PDO et paramètres JWT |
| `src/Controller/Api/` | Endpoints et sérialisation des réponses |
| `src/DTO/LoginInput.php` | Normalisation des données du login |
| `src/Service/LoginService.php` | Cas d'utilisation de connexion |
| `src/Repository/UserRepository.php` | Accès aux utilisateurs en base |
| `src/Middleware/JwtMiddleware.php` | Lit et vérifie le Bearer token |
| `src/Middleware/RequestAuthenticationMiddleware.php` | Réinitialise l'identité avant/après dispatch |
| `src/Security/Jwt/JwtTokenManager.php` | Émission, signature et validation des claims |
| `src/Security/Authentication/` | Identité vérifiée pendant la requête et contrôle des grants |

Le contrôleur ne doit pas effectuer directement la vérification cryptographique du JWT. Le service métier ne doit pas produire de réponse JSON.

## 5. Ajouter une route protégée

Pour une route privée, déclarer les exigences sur le groupe du contrôleur :

```php
use App\Middleware\JwtMiddleware;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: '/api', grants: ['ROLE_USER'], middleware: [JwtMiddleware::class])]
final class PrivateController
{
    #[Route(path: '/private', method: 'GET')]
    public function privateEndpoint(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Accès autorisé'], JSON_THROW_ON_ERROR);
    }
}
```

Ce contrôleur doit se trouver dans `App\Controller\Api`. Toutes ses actions héritent du middleware JWT et de `ROLE_USER`. **Les routes publiques (comme `POST /api/login`) doivent vivre dans un groupe séparé** : `grants: []` sur une méthode ne neutralise pas les grants de groupe. Voir `docs/jwt-api.md`.

## 6. Comprendre les réponses HTTP

| Statut | Signification dans ce projet |
| --- | --- |
| 200 | Connexion ou lecture du profil réussie |
| 400 | JSON d'entrée invalide |
| 401 | Identifiants incorrects ou token absent / invalide |
| 403 | Token valide, rôle requis absent |
| 404 | Route inconnue |
| 405 | Verbe HTTP non autorisé |

Les contrôles de signature, de date d'expiration et des claims JWT sont effectués par `JwtTokenManager`.

## 7. Tests

```bash
php tests/environment_loader_test.php
php tests/api_kernel_test.php
php tests/login_input_test.php
php tests/jwt_api_routes_test.php
php tests/jwt_authentication_test.php
```

Tests manuels conseillés : identifiants erronés, JSON invalide, profil sans en-tête, token falsifié, token expiré, token valide avec et sans `ROLE_USER`.

## 8. Sécurité et limites du POC

- Utiliser **HTTPS** en production ; éviter de transmettre un JWT via URL ou logs.
- Le secret doit être long, imprévisible et protégé ; ne pas réutiliser le même secret entre environnements.
- Les rôles du token sont figés à l'émission : un changement en base ne modifie pas les tokens déjà délivrés.
- Aucun refresh token, aucune révocation, aucune rotation automatique des clés.
- Ajouter en situation réelle : limitation des tentatives, CORS adapté, journalisation maîtrisée et politique de gestion des tokens.
- Le squelette ne gère pas automatiquement tous les scénarios d'erreur métier : compléter les tests d'intégration et les contrôles de sécurité.

Voir aussi `docs/jwt-api.md` pour les choix d'authentification et les limites de ce POC.

## 9. Déploiement et environnement

Le générateur produit un secret JWT aléatoire dans `.env` pour le développement uniquement. En production, **ne pas copier ce fichier** : définir `APP_ENV=prod` et injecter un `JWT_SECRET` distinct (ainsi que les variables SQL et JWT) dans l'environnement serveur. `EnvironmentLoader` n'ouvre aucun fichier dotenv en mode prod. Voir `docs/environments.md`.

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

Le gestionnaire JWT refuse un `JWT_TTL_SECONDS` nul ou négatif.
Les scénarios de claims, signatures, durées et isolation de l'identité sont
vérifiés avec `php tests/jwt_security_edge_test.php`.

Ces tests ne remplacent pas une validation avec une base MySQL réelle,
un serveur HTTP et des paramètres HTTPS de production.
