# mvc_starter — basic

> Référence autonome du profil `basic` de [mvc_script_generator](https://github.com/mithridatem/mvc_script_generator). Ce dépôt sert à **valider** les évolutions avant leur synchronisation dans le générateur.

**Workflow :** évolution technique → validation ici (tests et review) → synchronisation du générateur → test d'un projet généré.

Profils disponibles dans les branches : [basic](https://github.com/mithridatem/mvc_starter/tree/feat/basic-reference), [Web session/CSRF](https://github.com/mithridatem/mvc_starter/tree/variant/web), [API JWT](https://github.com/mithridatem/mvc_starter/tree/variant/api-jwt).

**Ne pas synchroniser de logique métier vers le générateur.** Les exercices sur les livres et les cas d'utilisation IDOR restent dans `project_exemple`.

---

# MVC Basic

## Guide pas à pas

Après génération, consulter **[docs/guide-utilisation.md](docs/guide-utilisation.md)** pour comprendre le parcours des requêtes, le rôle des composants et ajouter une fonctionnalité pas à pas.

Ce projet utilise `mithridatem/router` et `vlucas/phpdotenv` : pas de conteneur ni de librairie de validation.

```sh
composer install
php -S 127.0.0.1:8000 -t public
```

Ouvrez http://127.0.0.1:8000/.

### Exemple de formulaire

La page **http://127.0.0.1:8000/bonjour** propose un formulaire accessible en GET et traité en POST par `FormController::bonjour(RequestInterface $request)`.

Le contrôleur utilise `$request->post('name', '')` plutôt que `$_POST`, puis appelle `ExampleService` pour construire le message. Le template `templates/form.php` échappe les données affichées. Voir le guide pour le parcours détaillé.

Pour lancer le test après `composer install` :

```bash
php tests/form_request_test.php
```


- `public/index.php` : routeur et point d'entrée.
- `src/Controller` : entrée/sortie HTTP.
- `src/Service` : logique métier.
- `src/Repository` : persistance PDO.
- `src/Database/ConnectionInterface.php` : contrat commun de connexion PDO.
- `src/Database/ConnectionFactory.php` : choix du moteur MySQL, PostgreSQL ou SQLite.
- `src/Database/Mysql.php`, `Postgresql.php`, `Sqlite.php` : connexions facultatives.
- `src/Entity` : objets métier.
- `templates` : vues.

Le squelette n'a pas besoin de base de données pour la page d'accueil. Le fichier `.env`, s'il existe, est chargé par Dotenv, sans établir de connexion au démarrage. Pour utiliser une base : copier `.env.example` en `.env`, renseigner `DATABASE_DRIVER=mysql|pgsql|sqlite`, puis appeler `ConnectionFactory::create()->connect()` lors de la construction d'un repository. `Mysql::connectBdd()` reste compatible avec les anciens exercices. Voir [le guide des bases de données](docs/databases.md) et le guide d'utilisation.

Tests : `php tests/form_request_test.php` et `php tests/database_connection_test.php`.
