# Guide d'utilisation — Profil Basic

> **Objectif :** comprendre le cheminement d'une requête HTTP dans une architecture MVC simple, sans conteneur de dépendances, sans JWT et sans base de données obligatoire.

## 1. Démarrage rapide

Depuis le dossier **du projet généré** :

```bash
composer install
php -S 127.0.0.1:8000 -t public
```

Ouvrir [http://127.0.0.1:8000/](http://127.0.0.1:8000/). Il n'est pas nécessaire de créer une base MySQL pour afficher l'accueil.

Le fichier `.env` est seulement fourni à titre d'exemple dans ce profil : il **n'est pas chargé automatiquement**.

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
