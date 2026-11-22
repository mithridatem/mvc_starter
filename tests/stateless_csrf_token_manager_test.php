<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Security\StatelessCsrfTokenManager;

function checkStateless(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$secret = str_repeat('a', 64);
$cookie = bin2hex(random_bytes(32));
$_COOKIE['mvc_csrf_dev'] = $cookie;
$manager = new StatelessCsrfTokenManager($secret, false, 3600);
$token = $manager->generate('/login');

checkStateless(session_status() !== PHP_SESSION_ACTIVE, 'Le token stateless ne doit pas démarrer une session');
checkStateless($manager->isValid('/login', $token), 'Le token valide doit être accepté');
checkStateless(!$manager->isValid('/register', $token), 'Un token ne doit pas être valide sur une autre route');
checkStateless(!$manager->isValid('/login', null), 'Un token absent doit être rejeté');
checkStateless(!$manager->isValid('/login', ['invalid']), 'Une valeur non scalaire doit être rejetée');
checkStateless(!$manager->isValid('/login', 'invalid'), 'Une signature invalide doit être rejetée');

$_COOKIE['mvc_csrf_dev'] = bin2hex(random_bytes(32));
checkStateless(!$manager->isValid('/login', $token), 'Le token doit être lié au navigateur');
unset($_COOKIE['mvc_csrf_dev']);
checkStateless(!$manager->isValid('/login', $token), 'Le cookie manquant doit être rejeté');
$_COOKIE['mvc_csrf_dev'] = $cookie;

$otherKey = new StatelessCsrfTokenManager(str_repeat('b', 64), false);
checkStateless(!$otherKey->isValid('/login', $token), 'La signature doit dépendre de la clé serveur');
$expired = new StatelessCsrfTokenManager($secret, false, 1);
sleep(2);
checkStateless(!$expired->isValid('/login', $token), 'Un token expiré doit être rejeté');

try {
    new StatelessCsrfTokenManager('short', false);
    throw new RuntimeException('Une clé faible a été acceptée');
} catch (InvalidArgumentException $expected) {
    // Rejet attendu.
}

echo "Stateless CSRF: OK\n";
