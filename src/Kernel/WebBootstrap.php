<?php

declare(strict_types=1);

namespace App\Kernel;

/**
 * Web-only startup: environment, security headers and PHP session.
 * The JWT API variant intentionally does not use this class.
 */
final class WebBootstrap
{
    /**
     * Initialise l'environnement, les en-têtes de sécurité et la session PHP si nécessaire.
     * @param string $rootPath Chemin racine utilisé pour charger la configuration d'environnement.
     * @return void
     * @throws \InvalidArgumentException Si APP_ENV est invalide.
     */
    public function boot(string $rootPath): void
    {
        (new EnvironmentLoader())->load($rootPath);

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; script-src 'self'; object-src 'none'; frame-ancestors 'none';");

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        session_start();
    }
}
