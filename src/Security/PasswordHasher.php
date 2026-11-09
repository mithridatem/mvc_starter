<?php

declare(strict_types=1);

namespace App\Security;

/** Responsable uniquement de la représentation sécurisée des mots de passe. */
final class PasswordHasher
{
    /**
     * Hache un mot de passe en clair avec l'algorithme PHP par défaut.
     * @param string $password Mot de passe en clair.
     * @return string Empreinte à stocker, jamais le mot de passe original.
     */
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifie un mot de passe en clair contre son empreinte stockée.
     * @param string $plainPassword Mot de passe proposé.
     * @param string $hash Empreinte enregistrée.
     * @return bool Vrai lorsque le mot de passe correspond.
     */
    public function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }
}
