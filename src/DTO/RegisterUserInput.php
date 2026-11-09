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
     * Transforme les champs bruts d'inscription en DTO : nettoie les champs textuels mais préserve les mots de passe. Les valeurs non textuelles deviennent null.
     * @param array<string, mixed> $data Tableau issu du formulaire.
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
