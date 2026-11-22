<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Contrat partagé par les protections CSRF avec et sans session.
 * L'identifiant est le chemin de soumission du formulaire.
 */
interface CsrfTokenManagerInterface
{
    public function generate(string $formId): string;

    public function isValid(string $formId, mixed $token): bool;
}
