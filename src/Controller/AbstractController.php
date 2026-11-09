<?php

namespace App\Controller;

use Mithridatem\Routing\Http\RequestInterface;

abstract class AbstractController
{
    /**
     * Rend un template HTML complet et retourne son contenu.
     *
     * Les variables $title et $data sont accessibles dans le template.
     */
    protected function render(string $template, ?string $title = null, array $data = []): string
    {
        return $this->renderFile(
            __DIR__ . '/../../template/template_' . $this->validateViewName($template) . '.php',
            $data,
            $title
        );
    }

    /**
     * Rend un composant HTML réutilisable et retourne son contenu.
     *
     * Les données sont accessibles via $data dans le composant.
     */
    protected function renderComponent(string $component, array $data = []): string
    {
        return $this->renderFile(
            __DIR__ . '/../../template/component/component_' . $this->validateViewName($component) . '.php',
            $data
        );
    }

    /**
     * Charge un fichier PHP dans un buffer isolé et nettoie celui-ci en cas d'erreur.
     */
    private function renderFile(string $file, array $data, ?string $title = null): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Vue introuvable : ' . basename($file));
        }

        ob_start();

        try {
            require $file;
            return (string) ob_get_clean();
        } catch (\Throwable $error) {
            ob_end_clean();
            throw $error;
        }
    }

    /**
     * N'autorise que des noms simples (pas de chemin arbitraire).
     */
    private function validateViewName(string $name): string
    {
        if (!preg_match('/\A[a-zA-Z0-9_-]+\z/D', $name)) {
            throw new \InvalidArgumentException('Nom de vue invalide.');
        }

        return $name;
    }

    /**
     * Méthode pour envoyer une réponse JSON
     * @param array $data Les données à encoder en JSON
     * @param int $statusCode Le code de statut HTTP
     * @return void
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Indique si la requête correspond à une soumission HTTP POST.
     * Ne dépend pas de la présence d'un bouton submit nommé dans le formulaire.
     */
    protected function isFormSubmitted(RequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === 'POST';
    }

    /**
     * Méthode pour rediriger vers une route
     * @param string $route route de redirection
     * @return void
     */
    protected function redirect(string $route): void
    {
        header("Location: " . $route, true, 303);
        exit;
    }
}
