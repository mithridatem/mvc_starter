<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Middleware\CsrfMiddleware;
use App\Middleware\InvalidCsrfTokenException;
use App\Security\StatelessCsrfTokenManager;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Route;
use Mithridatem\Routing\Router;

function assertStatelessMiddleware(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$_COOKIE['mvc_csrf_dev'] = bin2hex(random_bytes(32));
$tokens = new StatelessCsrfTokenManager(str_repeat('x', 64), false);
$valid = $tokens->generate('/contact');
$router = new Router();
$executions = 0;
$router->map(Route::post('/contact', static function () use (&$executions): string {
    ++$executions;
    return 'OK';
})->withMiddleware(new CsrfMiddleware($tokens)));

assertStatelessMiddleware(
    $router->dispatch(new Request('/contact', 'POST', post: ['csrf_token' => $valid])) === 'OK',
    'La soumission valide doit passer sans session'
);
assertStatelessMiddleware(session_status() !== PHP_SESSION_ACTIVE, 'Le middleware ne doit pas démarrer de session');

foreach ([
    [],
    ['csrf_token' => 'invalid'],
    ['csrf_token' => ['unexpected']],
    ['csrf_token' => $tokens->generate('/other')],
] as $post) {
    try {
        $router->dispatch(new Request('/contact', 'POST', post: $post));
        throw new RuntimeException('Une requête invalide a été acceptée.');
    } catch (InvalidCsrfTokenException $expected) {
        assertStatelessMiddleware($executions === 1, 'Le contrôleur doit rester inaccessible.');
    }
}

echo "Stateless middleware integration: OK\n";
