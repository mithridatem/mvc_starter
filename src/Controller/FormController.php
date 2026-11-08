<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExampleService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Http\RequestInterface;

/**
 * Exemple pédagogique : récupérer un champ POST sans utiliser directement $_POST.
 */
final class FormController extends AbstractController
{
    #[Route(path: '/bonjour', method: 'GET', grants: [])]
    #[Route(path: '/bonjour', method: 'POST', grants: [])]
    public function bonjour(RequestInterface $request): void
    {
        $name = '';
        $message = '';
        $error = '';

        if ($this->isFormSubmitted($request)) {
            // RequestInterface expose post() ; elle masque l'accès direct à $_POST.
            $value = $request->post('name', '');

            if (!is_string($value) || trim($value) === '') {
                $error = 'Veuillez saisir votre prénom.';
            } else {
                $name = trim($value);
                $service = new ExampleService();
                $message = $service->greet($name);
            }
        }

        $this->render('form', 'Formulaire de bienvenue', [
            'name' => $name,
            'message' => $message,
            'error' => $error,
        ]);
    }
}
