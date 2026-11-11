<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Controller\ErrorController;
use App\Middleware\InvalidCsrfTokenException;
use Mithridatem\Routing\Exception\MethodNotAllowedException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\RouterException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Throwable;

/** Traduit les exceptions en statuts HTTP et délègue le rendu HTML au contrôleur d'erreur. */
final class WebErrorHandler
{
    public function __construct(private ErrorController $errorController) {}

    /**
     * Gère les erreurs HTTP sans divulguer les détails internes.
     *
     * @param Throwable $error Exception survenue pendant le traitement HTTP.
     * @return void
     */
    public function handle(Throwable $error): void
    {
        $status = match (true) {
            $error instanceof InvalidCsrfTokenException,
            $error instanceof UnauthorizedException => 403,
            $error instanceof RouteNotFoundException => 404,
            $error instanceof MethodNotAllowedException => 405,
            $error instanceof RouterException =>
                ($error->getCode() >= 400 && $error->getCode() <= 599)
                    ? $error->getCode()
                    : 500,
            default => 500,
        };

        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');

        if ($error instanceof MethodNotAllowedException) {
            header('Allow: ' . implode(', ', $error->getAllowedMethods()));
        }

        try {
            echo $this->errorController->error($status);
        } catch (Throwable) {
            // Même le template d'erreur peut échouer : fournir une réponse sûre.
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            header_remove('Allow');
            echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Erreur 500</title></head>'
                . '<body><h1>500 — Erreur interne du serveur</h1>'
                . '<p>Une erreur est survenue.</p></body></html>';
        }
    }
}
