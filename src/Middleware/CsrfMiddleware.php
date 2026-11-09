<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\CsrfTokenManager;
use Mithridatem\Routing\Context\RequestContextInterface;
use Mithridatem\Routing\Http\RequestInterface;
use Mithridatem\Routing\Middleware\MiddlewareInterface;

/**
 * Protects explicitly configured form routes against CSRF.
 *
 * The form identifier is the requested path, not a client-provided form ID.
 * Controllers must generate tokens using the same POST action path.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    /**
     *
     * @param CsrfTokenManager $tokens Gestionnaire des jetons CSRF associés à la session.
     */
    public function __construct(private CsrfTokenManager $tokens) {}

    /**
     * Vérifie le jeton CSRF d'une requête POST avant de passer au prochain traitement. Le chemin HTTP est utilisé comme identifiant du formulaire.
     * @param RequestContextInterface $context Requête à protéger.
     * @param callable $next Prochaine étape du pipeline de middlewares.
     * @return mixed Résultat de l'étape suivante.
     * @throws \LogicException Si le contexte ou la méthode HTTP sont incompatibles.
     * @throws InvalidCsrfTokenException Si le jeton est invalide.
     */
    public function process(RequestContextInterface $context, callable $next): mixed
    {
        if (!$context instanceof RequestInterface) {
            throw new \LogicException('CsrfMiddleware requires a RequestInterface.');
        }

        if ($context->getMethod() !== 'POST') {
            throw new \LogicException('CsrfMiddleware is intended for POST routes only.');
        }

        $formId = $context->getPath();

        if (!$this->tokens->isValid($formId, $context->post('csrf_token'))) {
            throw new InvalidCsrfTokenException('Token CSRF invalide');
        }

        return $next($context);
    }
}
