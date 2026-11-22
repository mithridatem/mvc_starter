# Comparaison des stratégies CSRF — profil Web avancé

Le projet conserve deux implémentations interchangeables derrière `CsrfTokenManagerInterface`.

| Mode | Stockage CSRF | Prérequis | Déploiement |
| --- | --- | --- | --- |
| `session` (par défaut) | `$_SESSION['csrf_tokens']` | Session PHP | Comportement historique |
| `stateless` | Aucun token stocké côté serveur | Clé HMAC + cookie navigateur | HTTPS obligatoire en production |

## Configuration

Dans l'environnement de développement :

```dotenv
CSRF_MODE=session
# Pour tester l'alternative :
# CSRF_MODE=stateless
# CSRF_SECRET=<secret aleatoire d'au moins 32 caracteres>
```

Générer une clé forte avec `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`.
Ne pas versionner la clé. En production, fournir `CSRF_MODE` et `CSRF_SECRET` via les secrets de l'hébergement. La stratégie stateless refuse le mode production si la requête n'utilise pas HTTPS.

Les formulaires restent inchangés : `$csrf->generate('/login')`, champ caché `csrf_token`, et middleware sur POST. Le middleware emploie le chemin de soumission comme identifiant.

## Fonctionnement stateless

1. Au premier GET du formulaire, le serveur émet un cookie aléatoire `__Host-mvc_csrf` en HTTPS (ou `mvc_csrf_dev` en HTTP de développement).
2. Il génère un token `timestamp.HMAC` dont la signature couvre le chemin POST, la date d'émission et la valeur du cookie.
3. Au POST, le navigateur renvoie le cookie et le champ caché ; le middleware vérifie l'expiration et la signature.
4. L'absence du cookie ou une signature incorrecte entraîne HTTP 403.

Le cookie est `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS, `Path=/` et n'a aucun attribut `Domain`. Le préfixe `__Host-` bloque les cookies injectés par un sous-domaine dans les navigateurs qui le prennent en charge. Une configuration HTTPS incorrecte ou une compromission de la clé invalide le modèle de sécurité. Le mode HTTP n'est destiné qu'au développement local.

**Limites :** un token peut être rejoué durant sa durée de vie (1 heure) ; il n'est pas à usage unique. Le mécanisme CSRF n'utilise pas la session PHP mais l'application Web conserve les sessions pour l'authentification et les rôles. Le secret doit être identique entre les instances d'une application et être renouvelé selon une procédure maîtrisée. Les requêtes qui modifient l'état doivent toutes être protégées, y compris au-delà de POST si elles sont introduites.

## Validation

```bash
php tests/csrf_token_manager_test.php
php tests/stateless_csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/auth_form_render_test.php
```

En complément, tester dans un vrai navigateur sous HTTPS (environnement de recette) : premier GET, POST valide, cookie absent, mauvais token, changement de navigateur, expiration, et rejet d'un cookie injecté depuis un sous-domaine. Vérifier le comportement du serveur/proxy HTTPS avant mise en production.
