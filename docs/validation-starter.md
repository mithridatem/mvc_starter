# Validation du socle Web avancé

Ce profil est une **référence technique autonome** destinée à valider les évolutions
avant de synchroniser le profil `advanced` de `mvc_script_generator`.

Il contient uniquement l'exemple d'authentification des utilisateurs nécessaire
pour tester la session, les DTO, les services, le repository et les middlewares.
Les entités et tables métier (livres, emprunts, etc.) restent dans les applications
de démonstration comme `project_exemple`.

## Contrôles automatisés

La CI teste PHP 8.3, 8.4 et 8.5 : manifeste Composer, installation des dépendances,
syntaxe PHP, tests applicatifs présents dans `tests/` et absence de tables
métier dans le schéma générique.

En local :

```bash
composer install
composer validate --no-check-publish
for test in tests/*_test.php tests/security/*.php; do php "$test" || exit 1; done
```

## Recette manuelle (MySQL / MariaDB, PostgreSQL ou SQLite)

Lire [docs/databases.md](databases.md) pour sélectionner `DATABASE_DRIVER`, installer l'extension PDO nécessaire et importer le schéma correspondant. Le test `tests/database_connection_test.php` couvre la sélection du driver et un CRUD SQLite temporaire si `pdo_sqlite` est installé.


1. Copier `.env.example` vers `.env`, renseigner la connexion SQL sans publier les secrets.
2. Importer le schéma correspondant au driver choisi (table utilisateur uniquement).
3. Démarrer `php -S 127.0.0.1:8000 -t public`.
4. Vérifier `GET /register` puis l'inscription via POST avec un jeton CSRF valide.
5. Vérifier l'échec d'un POST sans CSRF ou avec un token invalide (403).
6. Vérifier `GET /login` et une connexion valide ; contrôler la session authentifiée.
7. Vérifier la redirection des utilisateurs connectés depuis les routes réservées aux invités.
8. Vérifier qu'une route nécessitant `ROLE_USER` refuse un invité ou un utilisateur non autorisé.

## Critères de synchronisation

- CI verte et recette manuelle validée.
- Pas de classe, route ou table métier propre à la démonstration de bibliothèque.
- Toute évolution du socle est revue sur la branche `variant/web`.
- Après validation uniquement : synchroniser les fichiers correspondants dans
  `mvc_script_generator/templates/advanced`, puis générer et tester un nouveau projet.

La CI ne remplace pas le test des formulaires avec une vraie base de données.
