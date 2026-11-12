# Guide d'utilisation — Profil Basic

> **Objectif :** comprendre le cheminement d'une requête HTTP dans une architecture MVC simple, sans conteneur de dépendances, sans JWT et sans base de données obligatoire.

## 1. Démarrage rapide

Depuis le dossier **du projet généré** :

```bash
composer install
php -S 127.0.0.1:8000 -t public
```

Ouvrir [http://127.0.0.1:8000/](http://127.0.0.1:8000/). Il n'est pas nécessaire de créer une base de données pour afficher l'accueil.

Le fichier `.env` est chargé s'il existe, mais aucune connexion SQL n'est ouverte au démarrage.

## 2. Comprendre le parcours d'une requête

```text
Navigateur GET /
       |
       v
public/index.php (Router + découverte des contrôleurs)
       |
       v
HomeController::index()
       |
       v
ExampleService::greet()
       |
       v
AbstractController::render() -> templates/home.php -> HTML
```

| Emplacement | Rôle |
| --- | --- |
| `public/index.php` | Point d'entrée HTTP ; initialise le routeur et traite les erreurs 404/405 |
| `src/Controller/` | Reçoit la demande HTTP et décide du rendu |
| `src/Service/` | Regroupe la logique métier |
| `src/Repository/` | Emplacement prévu pour les requêtes de persistance (sans implémentation SQL fournie) |
| `src/Database/ConnectionInterface.php` | Contrat simple pour fournir un objet PDO |
| `src/Database/ConnectionFactory.php` | Sélection de MySQL, PostgreSQL ou SQLite via `DATABASE_DRIVER` |
| `src/Database/Mysql.php`, `Postgresql.php`, `Sqlite.php` | Création à la demande d'une connexion PDO |
| `src/Entity/` | Modèles métier |
| `templates/` | Fichiers PHP qui génèrent le HTML |
| `composer.json` | Dépendances et autoload PSR-4 |

## 3. Ajouter une page sans conteneur

**Exemple :** une page `/salut` qui appelle `ExampleService`.

Créer `src/Controller/SalutController.php` :

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExampleService;
use Mithridatem\Routing\Attribute\Route;

final class SalutController extends AbstractController
{
    #[Route(path: '/salut', method: 'GET', grants: [])]
    public function index(): void
    {
        $service = new ExampleService();
        $message = $service->greet('visiteur');
        $this->render('salut', 'Bonjour', ['message' => $message]);
    }
}
```

Créer `templates/salut.php` :

```php
<h1><?= htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8') ?></h1>
```

La découverte par attributs est automatique : **inutile d'ajouter manuellement la route dans `index.php`**.

Tester `http://127.0.0.1:8000/salut`.

## 4. Exemple fourni : formulaire GET/POST avec RequestInterface

Le projet généré propose **déjà** la page [http://127.0.0.1:8000/bonjour](http://127.0.0.1:8000/bonjour). Elle permet d'observer comment recevoir des données sans manipuler directement la superglobale `$_POST`.

- `templates/form.php` : formulaire HTML `method="post"`, champ `name="name"`.
- `src/Controller/FormController.php` : une action associée aux méthodes GET et POST.
- `src/Service/ExampleService.php` : prépare le message de bienvenue.

Le contrôleur utilise :

```php
use Mithridatem\Routing\Http\RequestInterface;

#[Route(path: '/bonjour', method: 'GET', grants: [])]
#[Route(path: '/bonjour', method: 'POST', grants: [])]
public function bonjour(RequestInterface $request): void
{
    $name = '';

    if ($this->isFormSubmitted($request)) {
        $value = $request->post('name', '');
        // Vérifier que la valeur reçue est une chaîne non vide.
        // Puis appeler ExampleService et rendre la vue.
    }
}
```

**À retenir :** `$request->getMethod()` permet de connaître la méthode HTTP ; `$request->post('name', '')` lit le champ du formulaire et fournit une valeur par défaut. La méthode `isFormSubmitted()` de `AbstractController` utilise également `RequestInterface`.

Le code fourni gère la saisie vide ou inattendue (par exemple un tableau au lieu d'un texte), puis échappe le contenu dynamique avec `htmlspecialchars()` lors du rendu. Ce contrôle simple **ne remplace pas** une validation métier complète.

Tester le résultat en navigateur, puis lancer :

```bash
php tests/form_request_test.php
```

Ce formulaire est volontairement **pédagogique et sans protection CSRF**. Il ne doit pas servir tel quel à modifier des données sensibles ou à déclencher des actions critiques. Le profil Advanced présente la protection CSRF via middleware.

## 5. Où placer la logique ?

- **Controller :** lit la requête, sollicite les services, choisit la réponse.
- **Service :** applique les règles métier, sans produire de HTML.
- **Repository :** centralise les accès SQL lorsqu'une base est ajoutée.
- **Template :** affiche les données, sans exécuter la logique métier.

Le `new ExampleService()` est volontairement explicite : il sert à apprendre la composition d'objets avant de découvrir l'autowiring.

## 6. Vérifications et pièges fréquents

```bash
php -l public/index.php
php -l src/Controller/SalutController.php
```

- **404 :** contrôler le chemin déclaré dans `#[Route]` et le nom du contrôleur.
- **405 :** vérifier le verbe HTTP déclaré (`GET`, `POST`, etc.).
- **Classe introuvable :** vérifier `namespace App\...`, l'arborescence et `composer install`.
- **XSS :** échapper les valeurs affichées dans les templates avec `htmlspecialchars()`.

## 7. Pour aller plus loin

Exercices suggérés : page avec paramètre, service de calcul, puis repository PDO. Lorsque plusieurs services dépendent les uns des autres, passer au profil **Advanced**, qui illustre l'injection automatique de dépendances.

**Limites :** ce projet est un point de départ pour l'apprentissage. Il ne fournit ni authentification, ni protection CSRF, ni stratégie de tests applicatifs complète. Ajouter les protections adaptées avant une mise en production.


## Connexion PDO multi-base (facultative)

Le profil Basic fournit `App\\Database\\Mysql` pour créer une connexion PDO **seulement si une fonctionnalité utilise une base de données**. Le point d'entrée charge le fichier `.env` avec `vlucas/phpdotenv` via `safeLoad()`, mais n'ouvre aucune connexion au démarrage. Le site d'accueil et les exercices sans SQL fonctionnent sans base de données.

1. Copier `.env.example` vers `.env`, choisir `DATABASE_DRIVER=mysql|pgsql|sqlite` et renseigner les variables adaptées (le fichier `.env` est ignoré par Git).
2. Créer sa base et ses tables, ou utiliser un fichier SQLite dans `var/`.
3. Créer un repository concret qui hérite de `AbstractRepository` et reçoit un `PDO` dans son constructeur.
4. Construire explicitement les objets nécessaires, sans autowiring :

```php
use App\\Database\\Mysql;
use App\\Repository\\UserRepository; // Exemple : classe à créer dans votre projet

$pdo = ConnectionFactory::create()->connect();
$repository = new UserRepository($pdo);
```

`Mysql::connectBdd()` reste disponible pour les exercices existants. La factory sélectionne une implémentation du contrat et retourne `PDO`. Consulter [le guide des moteurs SQL](databases.md) pour les extensions et le chemin SQLite. Les exceptions techniques ne doivent pas être affichées aux visiteurs en production.

Exécuter `php tests/database_connection_test.php` pour valider la sélection des moteurs et un exemple SQLite.

Cette approche illustre l'injection manuelle : le repository dépend de `PDO`, pas de la classe `Mysql`. `AbstractRepository` conserve uniquement sa connexion PDO ; chaque repository écrit ses propres requêtes préparées et utilise le typage explicite de `bindValue()` décrit plus bas.

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
$stmt = $this->pdo->prepare('SELECT * FROM user WHERE id_user = ?');
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();
```
