<?php

declare(strict_types=1);

namespace App\Security;

use Mithridatem\Routing\Auth\ArrayGrantChecker;
use Mithridatem\Routing\Auth\AuthenticationAwareGrantChecker;
use Mithridatem\Routing\Context\RequestContextInterface;
use Mithridatem\Routing\Router;

/**
 * Web-only navigation policy. Never trusts an unvalidated Referer header.
 * The last successfully visited GET page is kept in the PHP session.
 */
final class GuestNavigation
{
    private const SESSION_KEY = '_last_safe_page';

    /**
     * Définit la page locale de repli utilisée après un refus d'accès à une route réservée aux invités.
     * @param string $fallbackPath Chemin absolu local, sans query string ni fragment.
     * @throws \InvalidArgumentException Si la destination est externe ou invalide.
     */
    public function __construct(private string $fallbackPath = '/')
    {
        if (!$this->isLocalPath($fallbackPath)) {
            throw new \InvalidArgumentException('The fallback must be a local absolute path.');
        }
    }

    /**
     * Mémorise en session la dernière page GET locale et accessible, afin de retrouver une destination sûre.
     * @param RequestContextInterface $request Requête HTTP traitée avec succès.
     * @param Router $router Routeur permettant de vérifier l'accès.
     * @return void
     */
    public function rememberSuccessfulGet(RequestContextInterface $request, Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || strtoupper($request->getMethod()) !== 'GET') {
            return;
        }

        $path = $request->getPath();
        if ($this->isAccessiblePage($path, $router)) {
            $_SESSION[self::SESSION_KEY] = $path;
        }
    }

    /**
     * Returns a safe local destination only for a logged-in user denied on
     * a guest-only route. Other unauthorized errors keep their normal 403.
     */
    /**
     * Détermine une destination locale accessible pour un utilisateur connecté refusé sur une route IS_GUEST.
     * @param RequestContextInterface $request Requête refusée.
     * @param Router $router Routeur utilisé pour valider la destination.
     * @return string|null Chemin de redirection sûr ou null pour conserver la réponse 403.
     */
    public function destinationForDeniedGuest(RequestContextInterface $request, Router $router): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !self::isAuthenticated()) {
            return null;
        }

        $route = $this->matchedRoute($request->getPath(), $request->getMethod(), $router);
        if ($route === null || !in_array('IS_GUEST', $route->getGrants(), true)) {
            return null;
        }

        $previous = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_string($previous) && $previous !== $request->getPath() && $this->isAccessiblePage($previous, $router)) {
            return $previous;
        }

        return $this->fallbackPath !== $request->getPath()
            && $this->isAccessiblePage($this->fallbackPath, $router)
            ? $this->fallbackPath
            : null;
    }

    /**
     * Vérifie qu'un chemin local correspond à une route GET autorisée pour l'utilisateur courant et non réservée aux invités.
     * @param string $path Chemin à vérifier.
     * @param Router $router Routeur applicatif.
     * @return bool Vrai si la page peut être visitée.
     */
    private function isAccessiblePage(string $path, Router $router): bool
    {
        if (!$this->isLocalPath($path)) {
            return false;
        }

        $route = $this->matchedRoute($path, 'GET', $router);
        if ($route === null || in_array('IS_GUEST', $route->getGrants(), true)) {
            return false;
        }

        return $this->checker()->isGranted($route->getGrants());
    }

    /**
     * Retrouve la route correspondant au chemin et à la méthode, en tenant compte du préfixe du routeur.
     * @param string $path Chemin local demandé.
     * @param string $method Méthode HTTP.
     * @param Router $router Routeur contenant les routes.
     * @return \Mithridatem\Routing\RouteInterface|null Route correspondante ou null.
     */
    private function matchedRoute(string $path, string $method, Router $router): ?\Mithridatem\Routing\RouteInterface
    {
        if (!$this->isLocalPath($path)) {
            return null;
        }

        $base = rtrim($router->getBasePath(), '/');
        if ($base !== '' && $path !== $base && !str_starts_with($path, $base . '/')) {
            return null;
        }

        $relativePath = $base === '' ? $path : (substr($path, strlen($base)) ?: '/');
        return $router->getRoutes()->match($method, $relativePath)?->getRoute();
    }

    /**
     * Construit le vérificateur de droits à partir de l'utilisateur en session.
     * @return AuthenticationAwareGrantChecker Vérificateur de rôles et d'authentification.
     */
    private function checker(): AuthenticationAwareGrantChecker
    {
        return new AuthenticationAwareGrantChecker(
            static fn (): bool => self::isAuthenticated(),
            new ArrayGrantChecker($_SESSION['user']['grant'] ?? [])
        );
    }

    /**
     * Vérifie la présence d'un identifiant d'utilisateur dans la session PHP.
     * @return bool Vrai pour une session authentifiée.
     */
    private static function isAuthenticated(): bool
    {
        return isset($_SESSION['user'])
            && is_array($_SESSION['user'])
            && isset($_SESSION['user']['id']);
    }

    /**
     * Valide un chemin absolu local : interdit URL protocol-relative, antislashs, contrôles, query string et fragment.
     * @param string $path Chemin à contrôler.
     * @return bool Vrai si le chemin est local et sûr pour la navigation.
     */
    private function isLocalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, '\\')
            && !preg_match('/[\x00-\x1F\x7F]/', $path)
            && !str_contains($path, '?')
            && !str_contains($path, '#');
    }
}
