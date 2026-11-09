<?php

namespace App\Controller;

use Mithridatem\Routing\Http\RequestInterface;

abstract class AbstractController
{
    /**
     * Rend un template HTML complet et retourne son contenu.
     *
     * Les variables $title et $data sont accessibles dans le template.
     *
     * @param string $template Nom du template à afficher (sans préfixe ni extension).
     * @param string|null $title Titre de la page, facultatif.
     * @param array<string, mixed> $data Données transmises au template.
     * @return string HTML généré par le template.
     * @throws \InvalidArgumentException Si le nom du template est invalide.
     * @throws \RuntimeException Si le fichier du template est introuvable.
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
     *
     * @param string $component Nom du composant (sans préfixe ni extension).
     * @param array<string, mixed> $data Données transmises au composant.
     * @return string HTML généré par le composant.
     * @throws \InvalidArgumentException Si le nom du composant est invalide.
     * @throws \RuntimeException Si le fichier du composant est introuvable.
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
     *
     * @param string $file Chemin absolu du fichier PHP à charger.
     * @param array<string, mixed> $data Données accessibles dans le fichier.
     * @param string|null $title Titre accessible dans le fichier.
     * @return string Contenu HTML capturé.
     * @throws \RuntimeException Si le fichier est introuvable.
     * @throws \Throwable Si l'exécution du fichier échoue.
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
     *
     * @param string $name Nom de vue ou de composant à contrôler.
     * @return string Nom validé.
     * @throws \InvalidArgumentException Si le nom est invalide.
     */
    private function validateViewName(string $name): string
    {
        if (!preg_match('/\A[a-zA-Z0-9_-]+\z/D', $name)) {
            throw new \InvalidArgumentException('Nom de vue invalide.');
        }

        return $name;
    }

    /**
     * Envoie une réponse JSON avec le code de statut HTTP indiqué.
     *
     * @param array<string, mixed> $data Données à encoder en JSON.
     * @param int $statusCode Code de statut HTTP.
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
     *
     * @param RequestInterface $request Requête HTTP à examiner.
     * @return bool Vrai si la méthode HTTP est POST.
     */
    protected function isFormSubmitted(RequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === 'POST';
    }

    /**
     * Redirige vers une route avec le statut HTTP 303.
     *
     * @param string $route Chemin de destination de la redirection.
     * @return void
     */
    protected function redirect(string $route): void
    {
        header("Location: " . $route, true, 303);
        exit;
    }
}
