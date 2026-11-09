<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterUserInput;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\PasswordHasher;
use Mithridatem\Validation\Validator;

final class UserService
{
    /**
     *
     * @param UserRepository $userRepository Accès aux utilisateurs persistés.
     * @param Validator $validator Validateur des données d'inscription.
     * @param PasswordHasher $passwordHasher Service de hachage des mots de passe.
     */
    public function __construct(
        private UserRepository $userRepository,
        private Validator $validator,
        private PasswordHasher $passwordHasher,
    ) {}

    /**
     * Exécute l'inscription : valide les champs, vérifie l'unicité de l'email, hache le mot de passe et crée l'utilisateur.
     * @param RegisterUserInput $input Informations d'inscription validables.
     * @return array{validation?: array, errors?: string, success?: string} Résultat destiné à la vue.
     */
    public function register(RegisterUserInput $input): array
    {
        $errors = $this->validateRegistration($input);
        if ($errors !== []) {
            return $errors;
        }

        // Les champs requis sont non nuls une fois les contraintes validées.
        $email = $input->email;
        if ($this->userRepository->findByEmail($email) !== null) {
            return ['errors' => 'Le compte ' . $email . ' existe déjà'];
        }

        $user = $this->createUser($input);
        if ($this->userRepository->create($user) === null) {
            return ['errors' => "Impossible d'enregistrer le compte"];
        }

        return ['success' => 'Le compte ' . $email . ' a été ajouté avec succès'];
    }

    /**
     * Regroupe les violations de contraintes et la vérification de confirmation du mot de passe.
     * @param RegisterUserInput $input Données du formulaire à contrôler.
     * @return array{validation?: array, errors?: string} Erreurs à afficher, ou tableau vide.
     */
    private function validateRegistration(RegisterUserInput $input): array
    {
        $errors = [];
        $violations = $this->validator->validate($input);
        if ($violations !== []) {
            $errors['validation'] = $violations;
        }

        if ($input->password !== $input->confirmPassword) {
            $errors['errors'] = 'Les mots de passe ne sont pas identiques';
        }

        return $errors;
    }

    /**
     * Construit un utilisateur à partir du DTO et attribue le rôle utilisateur standard.
     * @param RegisterUserInput $input Données d'inscription à enregistrer.
     * @return User Entité dont le mot de passe est haché.
     */
    private function createUser(RegisterUserInput $input): User
    {
        $user = new User(
            $input->firstname,
            $input->lastname,
            $input->email,
            $this->passwordHasher->hash($input->password),
        );
        $user->setGrant('ROLE_USER');

        return $user;
    }
}
