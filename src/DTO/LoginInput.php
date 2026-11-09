<?php

declare(strict_types=1);

namespace App\DTO;

use Mithridatem\Validation\Attributes\Email;
use Mithridatem\Validation\Attributes\NotBlank;

final readonly class LoginInput
{
    /**
     *
     * @param string|null $email Adresse email soumise, ou null si absente/invalide.
     * @param string|null $password Mot de passe non modifié, ou null si absent/invalide.
     */
    public function __construct(
        #[NotBlank]
        #[Email]
        public ?string $email,
        #[NotBlank]
        public ?string $password,
    ) {}

    /**
     * Transforme les données brutes d'un formulaire en DTO de connexion. L'email est nettoyé ; les caractères du mot de passe sont préservés.
     * @param array<string, mixed> $data Valeurs issues de la requête HTTP.
     * @return self DTO prêt à être validé.
     */
    public static function fromArray(array $data): self
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        return new self(
            is_string($email) ? trim($email) : null,
            is_string($password) ? $password : null,
        );
    }
}
