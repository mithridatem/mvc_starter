<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Mithridatem\Routing\Exception\MethodNotAllowedException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

// Charge la configuration locale si un fichier .env existe.
// Aucun accès à MySQL n'est effectué au démarrage.
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$router = new Router();

// Découverte automatique : les contrôleurs abstraits sont ignorés.
(new AttributeRouteLoader())->loadDirectory(
    router: $router,
    directory: dirname(__DIR__) . '/src/Controller',
    namespace: 'App\\Controller'
);

try {
    $router->dispatch();
} catch (RouteNotFoundException $error) {
    http_response_code(404);
    echo 'Page introuvable';
} catch (MethodNotAllowedException $error) {
    http_response_code(405);
    echo 'Méthode HTTP interdite';
}
