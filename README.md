# MVC Basic

## Guide pas à pas

Après génération, consulter **[docs/guide-utilisation.md](docs/guide-utilisation.md)** pour comprendre le parcours des requêtes, le rôle des composants et ajouter une fonctionnalité pas à pas.

Ce projet utilise seulement `mithridatem/router` : pas de conteneur ni de librairie de validation.

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
- `src/Entity` : objets métier.
- `templates` : vues.

Le squelette n'a pas besoin de MySQL pour la page d'accueil. La configuration `.env` sert d'exemple et n'est pas lue automatiquement.
