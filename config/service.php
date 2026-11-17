<?php

declare(strict_types=1);

use App\Database\ConnectionFactory;
use App\Security\CsrfTokenManager;
use App\Security\CsrfTokenManagerInterface;
use App\Security\StatelessCsrfTokenManager;
use App\Service\UploadFileService;
use Mithridatem\Autowiring\Container;

return static function (Container $container): void {
    // Connexion créée au premier besoin, puis partagée.
    $container->factory(
        PDO::class,
        static fn (): PDO => ConnectionFactory::create()->connect()
    );

    // La stratégie historique reste active par défaut.
    $container->factory(
        CsrfTokenManagerInterface::class,
        static function (): CsrfTokenManagerInterface {
            $mode = $_ENV['CSRF_MODE'] ?? getenv('CSRF_MODE') ?: 'session';
            if ($mode === 'session') {
                return new CsrfTokenManager();
            }
            if ($mode !== 'stateless') {
                throw new \InvalidArgumentException('CSRF_MODE doit être session ou stateless.');
            }

            $secret = $_ENV['CSRF_SECRET'] ?? getenv('CSRF_SECRET') ?: '';
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $environment = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'dev';
            if ($environment === 'prod' && !$https) {
                throw new \LogicException('Le CSRF stateless requiert HTTPS en production.');
            }

            return new StatelessCsrfTokenManager((string) $secret, $https);
        }
    );

    // Service d'upload fourni comme exemple d'injection d'un paramètre scalaire.
    $container->configure(UploadFileService::class, [
        'uploadDirectory' => $_ENV['UPLOAD_DIRECTORY'] ?? 'public/asset/image/upload',
    ]);

    // Découvrir les implémentations des interfaces de repositories.
    /* $container->discover(
        dirname(__DIR__) . '/src/Repository',
        'App\\Repository'
    ); */
};
