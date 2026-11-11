# Choisir le moteur de base de données (variant/web)

La connexion est sélectionnée à partir de `DATABASE_DRIVER` ; les repositories continuent de recevoir `PDO` par injection de dépendances. `ConnectionInterface::connect(): PDO` définit le contrat ; `ConnectionFactory` sélectionne les implémentations `Mysql`, `Postgresql` et `Sqlite`. Le changement ne nécessite pas de modifier le contrôleur ou le service.

## MySQL / MariaDB (par défaut)

Installer l'extension PHP `pdo_mysql`, copier `.env.example` vers `.env`, conserver `DATABASE_DRIVER=mysql`, puis importer `db.sql` comme auparavant. `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USERNAME` et `DATABASE_PASSWORD` configurent la connexion. `Mysql::connectBdd()` reste disponible pour compatibilité.

## PostgreSQL

Installer `pdo_pgsql`. Dans `.env` : `DATABASE_DRIVER=pgsql`, `DATABASE_PORT=5432` et les identifiants PostgreSQL. Créer d'abord la base, puis importer `database/postgresql.sql` :

```bash
psql -d mvc_starter_web -f database/postgresql.sql
```

Si nécessaire, fournir les options de connexion via les variables standard `PGHOST`, `PGUSER`, etc., ou utiliser les paramètres de `.env` pour la connexion PHP. Le schéma est compatible avec le `RETURNING id_user` de `UserRepository`.

## SQLite

Installer `pdo_sqlite`, puis définir :

```dotenv
DATABASE_DRIVER=sqlite
DATABASE_PATH=var/database.sqlite
```

Le dossier `var/` existe dans ce starter et les fichiers `.sqlite` sont ignorés par Git. Initialiser le schéma :

```bash
sqlite3 var/database.sqlite < database/sqlite.sql
```

Le chemin relatif est résolu depuis la racine du projet, quel que soit le répertoire de lancement. Ne jamais placer le fichier SQLite dans `public/` ni committer une base contenant des données. Les variables réseau `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_USERNAME` et `DATABASE_PASSWORD` ne sont pas utilisées avec SQLite.

## Compatibilité et limites

- Seule l'extension `ext-pdo` est imposée par Composer ; installer **une extension PDO correspondant au driver sélectionné**.
- Le modèle de table `user` et la colonne `grant` ont des noms réservés : `UserRepository` les échappe selon le driver PDO.
- Les différents dialectes SQL ne sont pas interchangeables : adapter les futurs schémas et les éventuelles requêtes spécifiques au moteur choisi.
- Il ne s'agit ni d'un ORM, ni d'une migration automatique : sélectionner un autre driver ne transfère pas les données existantes.
- Pour tester : `php tests/database_connection_test.php` et la recette inscription/connexion avec le moteur choisi. Le test SQLite utilise une base temporaire, si `pdo_sqlite` est disponible.
