<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Présente des pages d'erreur HTML sans exposer les détails des exceptions.
 * Ce contrôleur est appelé directement par WebErrorHandler (sans route HTTP).
 */
final class ErrorController extends AbstractController
{
    /**
     * Génère une page d'erreur adaptée au statut HTTP.
     *
     * @param int $status Statut HTTP de l'erreur.
     * @return string Page HTML à envoyer au navigateur.
     */
    public function error(int $status): string
    {
        $errors = [
            401 => [
                'title' => 'Authentification nécessaire',
                'message' => 'Veuillez vous connecter pour accéder à cette page.',
                'link' => '/login',
                'linkText' => 'Se connecter',
            ],
            403 => [
                'title' => 'Accès interdit',
                'message' => 'Vous ne disposez pas des droits nécessaires pour accéder à cette page.',
                'link' => '/',
                'linkText' => "Retour à l'accueil",
            ],
            404 => [
                'title' => 'Page introuvable',
                'message' => 'La page demandée est introuvable.',
                'link' => '/',
                'linkText' => "Retour à l'accueil",
            ],
            405 => [
                'title' => 'Méthode non autorisée',
                'message' => 'Cette méthode HTTP n’est pas autorisée pour cette page.',
                'link' => '/',
                'linkText' => "Retour à l'accueil",
            ],
            500 => [
                'title' => 'Erreur interne du serveur',
                'message' => 'Une erreur est survenue. Veuillez réessayer ultérieurement.',
                'link' => '/',
                'linkText' => "Retour à l'accueil",
            ],
        ];

        $displayStatus = isset($errors[$status]) ? $status : 500;
        $error = $errors[$displayStatus];

        return $this->render('error', $error['title'], [
            'status' => $displayStatus,
            ...$error,
        ]);
    }
}
