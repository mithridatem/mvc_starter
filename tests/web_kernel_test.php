<?php

declare(strict_types=1);

// Run: php tests/web_kernel_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\ErrorController;
use App\Kernel\RouterFactory;
use App\Kernel\WebErrorHandler;
use App\Middleware\CsrfMiddleware;
use App\Middleware\InvalidCsrfTokenException;
use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Mithridatem\Routing\Group\GroupedRoute;

function checkKernel(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

session_start();
$_SESSION['user'] = ['id' => 1, 'grant' => ['ROLE_USER']];

$router = (new RouterFactory(new Container(), dirname(__DIR__)))->create();
foreach (['/login', '/register'] as $path) {
    $get = $router->getRoutes()->match('GET', $path);
    $post = $router->getRoutes()->match('POST', $path);
    checkKernel($get !== null && $post !== null, 'Missing web route: ' . $path);
    checkKernel($get->getRoute() instanceof GroupedRoute, 'GET should use RouteGroup: ' . $path);
    checkKernel($post->getRoute() instanceof GroupedRoute, 'POST should use RouteGroup: ' . $path);
    checkKernel($get->getRoute()->getGrants() === ['IS_GUEST'] && $post->getRoute()->getGrants() === ['IS_GUEST'], 'Auth forms must be guest-only: ' . $path);
    checkKernel($get->getRoute()->getMiddleware() === [], 'CSRF must not be on GET: ' . $path);
    checkKernel($post->getRoute()->getMiddleware() === [CsrfMiddleware::class], 'CSRF missing on POST: ' . $path);
}
checkKernel($router->getRoutes()->match('POST', '/api/login') === null, 'API route leaked into Web profile');
checkKernel($router->getRoutes()->match('GET', '/api/profile') === null, 'API profile leaked into Web profile');

$controller = new ErrorController();
$handler = new WebErrorHandler($controller);
foreach ([
    [new InvalidCsrfTokenException('<invalid>'), 403, 'Accès interdit'],
    [new UnauthorizedException(), 403, 'Accès interdit'],
    [new RouteNotFoundException(), 404, 'Page introuvable'],
    [new RuntimeException('secret-do-not-display'), 500, 'Erreur interne du serveur'],
] as [$error, $status, $expectedText]) {
    ob_start();
    try {
        $handler->handle($error);
        $html = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
    checkKernel(http_response_code() === $status, 'Incorrect HTTP status: ' . $status);
    checkKernel(str_contains($html, $expectedText), 'Unexpected HTML error response: ' . $status);
    checkKernel(str_contains($html, '<!doctype html>'), 'Error view must be a full HTML page');
    checkKernel(str_contains($html, 'href="/"'), 'Missing navigation link');
    checkKernel(!str_contains($html, '<invalid>') && !str_contains($html, '&lt;invalid&gt;') && !str_contains($html, 'secret-do-not-display'), 'Sensitive error details leaked');
}

$loginHtml = $controller->error(401);
checkKernel(str_contains($loginHtml, 'href="/login"'), '401 must offer login');
checkKernel(str_contains($loginHtml, 'Se connecter'), 'Missing login action');

$genericHtml = $controller->error(418);
checkKernel(str_contains($genericHtml, '500'), 'Unknown error status must display generic 500 view');

unset($_SESSION['user']);
session_write_close();
echo "Web bootstrap/router/error handler: OK\n";
