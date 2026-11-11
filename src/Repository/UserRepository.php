<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Repository\AbstractRepository;
use App\Entity\User;
use Override;
use PDO;

final class UserRepository extends AbstractRepository
{
    /**
     *
     * @param PDO $connect Connexion PDO injectée par le conteneur.
     */
    public function __construct(PDO $connect)
    {
        return parent::__construct($connect);
    }

    #[Override]
    /**
     * Recherche un utilisateur par identifiant et hydrate son entité.
     * @param int $id Identifiant en base de données.
     * @return User|null Utilisateur trouvé ou null.
     */
    public function find(int $id): ?User
    {
        $table = $this->quoteIdentifier('user');
        $grant = $this->quoteIdentifier('grant');
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email, 
            u.hash_password, u.created_at, u.updated_at, 
            u.img_profil, u.{$grant} FROM {$table} AS u 
            WHERE u.id_user = ?
            ORDER BY u.id_user
            SQL;
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(1, $id, \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        $user = User::hydrate($data);
        $user->setId((int) $data['id_user']);
        return $user;
    }

    #[Override]
    /**
     * Récupère les colonnes publiques de tous les utilisateurs sous forme de tableaux associatifs.
     * @return list<array<string, mixed>> Lignes issues de la base de données.
     */
    public function findAll(): array
    {
        $table = $this->quoteIdentifier('user');
        $grant = $this->quoteIdentifier('grant');
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email, 
            u.img_profil FROM {$table} AS u ORDER BY u.id_user
            SQL;
        return $this->connect
            ->query($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    #[Override]
    /**
     * Enregistre une entité User, renseigne son identifiant et renvoie null si l'insertion échoue.
     * @param Entity $entity Entité à insérer ; doit être un utilisateur.
     * @return User|null Entité persistée ou null en cas d'erreur PDO.
     * @throws \InvalidArgumentException Si l'entité n'est pas un User.
     */
    public function create(Entity $entity): ?User
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('UserRepository attend une entité User.');
        }

        $table = $this->quoteIdentifier('user');
        $grant = $this->quoteIdentifier('grant');
        $sql = <<<SQL
            INSERT INTO {$table}(firstname, lastname, email, hash_password, created_at,
            img_profil, {$grant}) VALUES (?,?,?,?,?,?,?)
            SQL;

        // PostgreSQL ne garantit pas un identifiant utilisable via lastInsertId() sans séquence.
        $isPostgresql = $this->connect->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
        if ($isPostgresql) {
            $sql .= ' RETURNING id_user';
        }

        try {
            $stmt = $this->connect->prepare($sql);
            $firstname = $entity->getFirstname();
            $lastname = $entity->getLastname();
            $email = $entity->getEmail();
            $password = $entity->getHashPassword();
            $image = $entity->getImgProfil();
            $grant = $entity->getGrant();

            $stmt->bindValue(1, $firstname, $firstname === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(2, $lastname, $lastname === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(3, $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(4, $password, $password === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(5, $entity->getCreatedAt()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(6, $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(7, $grant, $grant === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();

            $entity->setId((int) ($isPostgresql ? $stmt->fetchColumn() : $this->connect->lastInsertId()));
            return $entity;
        } catch (\PDOException $e) {
            // Laisser le service distinguer échec et succès sans afficher une erreur SQL.
            return null;
        }
    }

    /**
     * Recherche un utilisateur via son adresse email et hydrate l'entité.
     * @param string $email Adresse email exacte.
     * @return User|null Utilisateur trouvé ou null.
     */
    public function findByEmail(string $email): ?User
    {
        $table = $this->quoteIdentifier('user');
        $grant = $this->quoteIdentifier('grant');
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email,
                   u.hash_password, u.created_at, u.updated_at,
                   u.img_profil, u.{$grant}
            FROM {$table} AS u
            WHERE u.email = ?
            SQL;

        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        $user = User::hydrate($data);
        $user->setId((int) $data['id_user']);
        return $user;
    }
    /**
     * Protège les deux identifiants SQL réservés selon le moteur PDO.
     * Les noms sont constants et jamais dérivés des entrées utilisateur.
     */
    private function quoteIdentifier(string $identifier): string
    {
        if (!in_array($identifier, ['user', 'grant'], true)) {
            throw new \InvalidArgumentException('Identifiant SQL non autorisé.');
        }

        return match ($this->connect->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            'mysql' => "`{$identifier}`",
            'pgsql', 'sqlite' => '"' . $identifier . '"',
            default => throw new \RuntimeException('Driver PDO non pris en charge.'),
        };
    }
}
