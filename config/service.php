<?php

declare(strict_types=1);

use App\Database\Mysql;
use App\Service\UploadFileService;
use Mithridatem\Autowiring\Container;

return static function (Container $container): void {
    // Connexion créée au premier besoin, puis partagée.
    $container->factory(
        PDO::class,
        static fn (): PDO => Mysql::connectBdd()
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
