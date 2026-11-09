<?php

declare(strict_types=1);

namespace App\Middleware;

use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Middleware\MiddlewareInterface;
use Mithridatem\Routing\Middleware\MiddlewareResolverInterface;

/** Keeps routing independent of the application's autowiring implementation. */
final class ContainerMiddlewareResolver implements MiddlewareResolverInterface
{
    /**
     *
     * @param Container $container Conteneur d'injection de dépendances applicatif.
     */
    public function __construct(private Container $container) {}

    /**
     * Résout un middleware par son nom de classe et vérifie son contrat.
     * @param string $middleware Nom de classe du middleware à résoudre.
     * @return MiddlewareInterface Middleware prêt à être exécuté.
     * @throws \LogicException Si le service ne respecte pas MiddlewareInterface.
     */
    public function resolve(string $middleware): MiddlewareInterface
    {
        $instance = $this->container->get($middleware);

        if (!$instance instanceof MiddlewareInterface) {
            throw new \LogicException(sprintf(
                'Service %s does not implement MiddlewareInterface.',
                $middleware
            ));
        }

        return $instance;
    }
}
