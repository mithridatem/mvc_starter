<?php

declare(strict_types=1);

namespace App\DTO;

use Mithridatem\Validation\Attributes\Email;
use Mithridatem\Validation\Attributes\Length;
use Mithridatem\Validation\Attributes\NotBlank;

/**
 * Données propres au cas d'utilisation "inscrire un utilisateur".
 * La confirmation du mot de passe n'appartient pas à l'entité persistée.
 */
final readonly class RegisterUserInput
{
    /**
     * Construit les données d'inscription sans logique de persistance.
     *
     * @param string|null $firstname Prénom du formulaire.
     * @param string|null $lastname Nom du formulaire.
     * @param string|null $email Adresse email.
     * @param string|null $password Mot de passe non modifié.
     * @param string|null $confirmPassword Confirmation non modifiée.
     */
    public function __construct(
        #[NotBlank]
        #[Length(min: 2, max: 50)]
        public ?string $firstname,
        #[NotBlank]
        #[Length(min: 2, max: 50)]
        public ?string $lastname,
        #[NotBlank]
        #[Email]
        public ?string $email,
        #[NotBlank]
        #[Length(min: 4, max: 50)]
        public ?string $password,
        #[NotBlank]
        public ?string $confirmPassword,
    ) {}

    /**
     * Convertit un tableau de champs HTTP en DTO d'inscription.
     *
     * Les champs textuels sont nettoyés ; les mots de passe sont préservés.
     * Une valeur non textuelle est remplacée par null.
     *
     * @param array<string, mixed> $data Champs issus du formulaire.
     * @return self Entrée d'inscription à valider.
     */
    public static function fromArray(array $data): self
    {
        $text = static function (mixed $value): ?string {
            return is_string($value) ? trim($value) : null;
        };
        $password = static function (mixed $value): ?string {
            return is_string($value) ? $value : null;
        };

        return new self(
            $text($data['firstname'] ?? null),
            $text($data['lastname'] ?? null),
            $text($data['email'] ?? null),
            $password($data['password'] ?? null),
            $password($data['confirm_password'] ?? null),
        );
    }
}
