# Choisir une base de données — profil Basic

Ce projet peut fonctionner **sans aucune base de données**. La connexion PDO est créée uniquement lorsqu'un repository en a besoin, sans conteneur d'injection ni ORM.

## Sélection du moteur

Copier `.env.example` en `.env` puis définir `DATABASE_DRIVER` :

- `mysql` (valeur par défaut) : MySQL ou MariaDB, extension PHP `pdo_mysql` et paramètres `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USERNAME`, `DATABASE_PASSWORD`.
- `pgsql` : PostgreSQL, extension `pdo_pgsql`, paramètres de connexion réseau (`DATABASE_PORT=5432` normalement).
- `sqlite` : SQLite, extension `pdo_sqlite`, chemin de fichier `DATABASE_PATH=var/database.sqlite`. Le répertoire parent doit exister. Créer le fichier ou laisser SQLite le créer à l'ouverture.

Aucun schéma n'est fourni dans le profil Basic : les tables sont définies lorsque l'apprenant introduit un repository. Il n'y a ni génération de migrations ni conversion automatique des données SQL entre moteurs.

## Exemple de connexion explicite

```php
use App\Database\ConnectionFactory;
use PDO;

$pdo = ConnectionFactory::create()->connect();

// Exemple de repository concret à créer au fil des exercices :
// $repository = new MyRepository($pdo);
```

`ConnectionFactory` retourne une implémentation de `ConnectionInterface::connect(): PDO`. Les classes concrètes `Mysql`, `Postgresql` et `Sqlite` sont dans `src/Database/`. La méthode historique `Mysql::connectBdd()` est conservée.

Le chemin SQLite relatif est résolu depuis la racine du projet. Conserver la base **hors de `public/`** ; les fichiers `var/*.sqlite` sont ignorés par Git. Les identifiants SQL et les types de colonnes peuvent dépendre du moteur choisi : PDO n'unifie pas les dialectes SQL.

Tester la sélection des adaptateurs et la création d'une table temporaire SQLite :

```bash
php tests/database_connection_test.php
```

Les extensions des trois moteurs ne sont pas toutes obligatoires : installer celle correspondant à votre configuration `DATABASE_DRIVER`.
