<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\LoginInput;
use App\Repository\UserRepository;
use App\Security\PasswordHasher;
use Mithridatem\Validation\Validator;

final class LoginService
{
    /**
     * Initialise les dépendances du service.
     *
     *
     * @param UserRepository $users Dépôt des utilisateurs.
     *
     * @param Validator $validator Validation des identifiants soumis.
     *
     * @param PasswordHasher $hasher Vérification des mots de passe.
     */
    public function __construct(
        private UserRepository $users,
        private Validator $validator,
        private PasswordHasher $hasher,
    ) {}

    /**
     * Valide les identifiants et vérifie le mot de passe sans gérer directement la session HTTP.
     *
     * @param LoginInput $input Identifiants soumis.
     * @return array{validation?: array, errors?: string, user?: \App\Entity\User} Utilisateur authentifié ou erreurs destinées au contrôleur.
     */
    public function login(LoginInput $input): array
    {
        $violations = $this->validator->validate($input);
        if ($violations !== []) {
            return ['validation' => $violations];
        }

        $user = $this->users->findByEmail($input->email);
        if ($user === null || !$this->hasher->verify($input->password, $user->getHashPassword() ?? '')) {
            return ['errors' => 'Email ou mot de passe incorrect'];
        }

        return ['user' => $user];
    }
}
