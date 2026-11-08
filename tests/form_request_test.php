<?php

declare(strict_types=1);

// Run: php tests/form_request_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\FormController;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

function checkForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$router = new Router();
(new AttributeRouteLoader())->loadControllers($router, [FormController::class]);

checkForm($router->getRoutes()->match('GET', '/bonjour') !== null, 'Missing GET /bonjour route');
checkForm($router->getRoutes()->match('POST', '/bonjour') !== null, 'Missing POST /bonjour route');

$controller = new FormController();

ob_start();
$controller->bonjour(new Request('/bonjour', 'GET'));
$get = (string) ob_get_clean();
checkForm(str_contains($get, 'method="post"'), 'Form is not shown on GET');
checkForm(!str_contains($get, 'Bonjour Alice'), 'Unexpected submitted state on GET');

ob_start();
$controller->bonjour(new Request('/bonjour', 'POST', post: ['name' => 'Alice']));
$valid = (string) ob_get_clean();
checkForm(str_contains($valid, 'Bonjour Alice'), 'POST value was not passed to ExampleService');

ob_start();
$controller->bonjour(new Request('/bonjour', 'POST', post: ['name' => '  ']));
$blank = (string) ob_get_clean();
checkForm(str_contains($blank, 'Veuillez saisir votre prénom.'), 'Blank name must be rejected');

ob_start();
$controller->bonjour(new Request('/bonjour', 'POST', post: ['name' => ['unexpected']]));
$invalid = (string) ob_get_clean();
checkForm(str_contains($invalid, 'Veuillez saisir votre prénom.'), 'Array input must be rejected');

ob_start();
$controller->bonjour(new Request('/bonjour', 'POST', post: ['name' => '<script>alert(1)</script>']));
$escaped = (string) ob_get_clean();
checkForm(!str_contains($escaped, '<script>'), 'HTML user input is not escaped');
checkForm(str_contains($escaped, '&lt;script&gt;'), 'Escaped user input not displayed');

echo "Basic RequestInterface form: OK\n";
