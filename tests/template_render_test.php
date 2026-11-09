<?php

declare(strict_types=1);

// Lancer : php tests/template_render_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\AbstractController;

final class RenderingProbe extends AbstractController
{
    public function page(string $name, ?string $title = null, array $data = []): string
    {
        return $this->render($name, $title, $data);
    }

    public function component(string $name, array $data = []): string
    {
        return $this->renderComponent($name, $data);
    }
}

function checkRender(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$controller = new RenderingProbe();
$level = ob_get_level();

ob_start();
try {
    $html = $controller->page('home', '<Accueil>');
    checkRender(ob_get_contents() === '', 'render() écrit directement dans la sortie');
    checkRender(str_contains($html, 'Bienvenue sur Book Library'), 'HTML du template absent');
} finally {
    ob_end_clean();
}

$error = (object) ['message' => '<Erreur à échapper>'];
$component = $controller->component('error_validation', ['errors' => [$error]]);
checkRender(str_contains($component, '&lt;Erreur à échapper&gt;'), 'Composant non rendu ou non échappé');
checkRender(!str_contains($component, '<Erreur à échapper>'), 'Composant non échappé');

$login = $controller->page('login', 'Connexion', [
    'csrf_token' => 'token-test',
    'validation' => ['email' => [$error]],
]);
checkRender(str_contains($login, 'value="token-test"'), 'Données non transmises au template');
checkRender(str_contains($login, '&lt;Erreur à échapper&gt;'), 'Composant imbriqué absent');

foreach (['../secret', 'missing'] as $name) {
    try {
        $controller->page($name);
        throw new RuntimeException('Vue invalide ou absente acceptée : ' . $name);
    } catch (InvalidArgumentException $exception) {
        checkRender($name === '../secret', 'Mauvaise erreur pour une vue absente');
    } catch (RuntimeException $exception) {
        checkRender($name === 'missing', 'Mauvaise erreur pour un chemin invalide');
    }
    checkRender(ob_get_level() === $level, 'Fuite de buffer après erreur');
}

checkRender(ob_get_level() === $level, 'Fuite de buffer');
echo "Template/component rendering : OK\n";
