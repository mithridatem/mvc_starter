<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;

/** Encapsule la représentation de l'utilisateur authentifié en session PHP. */
final class UserSessionManager
{
    /**
     * Authentifie l'utilisateur en session, régénère l'identifiant et conserve son ID, son email et ses rôles.
     * @param User $user Utilisateur authentifié.
     * @return void
     * @throws \LogicException Si aucune session PHP n'est active.
     */
    public function login(User $user): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \LogicException('Une session PHP doit être démarrée avant la connexion.');
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'grant' => array_values(array_filter(
                array_map('trim', explode(',', $user->getGrant() ?? '')),
                static fn (string $grant): bool => $grant !== ''
            )),
        ];
    }

    /**
     * Déconnecte l'utilisateur, efface ses données d'authentification et régénère l'identifiant de session.
     * @return void
     * @throws \LogicException Si aucune session PHP n'est active.
     */
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \LogicException('Une session PHP doit être démarrée avant la déconnexion.');
        }

        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}
