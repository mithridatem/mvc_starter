# Validation du socle API JWT

Cette branche est une **référence technique autonome** avant synchronisation
du profil `api-jwt` dans `mvc_script_generator`. Pas de session PHP, pas de
formulaire Web et pas de tables métier.

## Vérifications automatisées

La CI exécute les vérifications en PHP 8.3, 8.4 et 8.5 :

- validation du manifeste Composer et installation des dépendances ;
- vérification de la syntaxe de tous les fichiers PHP ;
- tests des routes API, de l'authentification JWT et des grants ;
- tests adversariaux (signature, algorithme, expiration, issuer, audience,
  claims invalides et absence de fuite d'identité entre requêtes) ;
- tests du bootstrap, de l'environnement et du repository ;
- garde-fous sur la séparation API/Web et sur l'absence de schéma métier.

En local :

```bash
composer install
composer validate --no-check-publish
for test in tests/*_test.php tests/security/*.php; do php "$test" || exit 1; done
```

## Recette manuelle (MySQL / MariaDB, PostgreSQL, SQLite)

Consulter [le guide de configuration SQL](databases.md) : choisir `DATABASE_DRIVER`, installer l'extension PDO appropriée, importer le schéma du moteur, puis reprendre la validation JWT. La CI teste la sélection des adaptateurs et le CRUD utilisateur SQLite sur une base temporaire ; les validations PostgreSQL/MySQL avec serveurs réels restent manuelles.

1. Copier `.env.example` dans `.env` et définir `APP_ENV=dev`.
2. Définir `JWT_SECRET` avec **64 caractères hexadécimaux aléatoires**, par exemple
   `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Ne jamais le committer.
3. Conserver `JWT_ISSUER=mvc-starter` et `JWT_AUDIENCE=mvc-starter-api`
   (ou personnaliser les deux valeurs de façon cohérente).
4. Configurer la base et importer `db.sql`, `database/postgresql.sql` ou `database/sqlite.sql` selon le moteur (table utilisateur seulement).
5. Démarrer `php -S 127.0.0.1:8000 -t public`.
6. Créer un utilisateur de test **avec un hash** issu de `password_hash` et le
   rôle `ROLE_USER` dans la base de test.
7. Vérifier `POST /api/login` avec identifiants valides et invalides.
8. Vérifier `GET /api/profile` avec token valide, sans token et avec token
   altéré : succès pour le token valide et **401** pour absence/invalidité.
9. Vérifier qu'un token valide dépourvu de `ROLE_USER` est refusé (**403**).
10. Vérifier qu'un token expiré est rejeté et qu'aucune session PHP n'est utilisée.

## Limites et sécurité en production

Le profil est pédagogique : pas de refresh token, de révocation ni de
rotation automatique des secrets. Ne pas déployer sans HTTPS, limitation
des tentatives de login, contrôle des autorisations au niveau ressource,
politique d'expiration/révocation et supervision.

L'authentification JWT n'empêche pas les failles IDOR : chaque Use Case
métier doit vérifier l'autorisation sur la ressource concernée. Ces règles
restent dans les applications, et non dans le squelette générique.

## Condition de synchronisation

La CI doit être verte, la recette manuelle effectuée et la PR revue puis
fusionnée dans `variant/api-jwt`. Ensuite seulement, mettre à jour
`mvc_script_generator/templates/api-jwt` et tester un projet fraîchement généré.
