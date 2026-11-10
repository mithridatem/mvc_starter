<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Repository\AbstractRepository;
use App\Entity\User;
use Override;
use PDO;

final class UserRepository extends AbstractRepository
{
    public function __construct(PDO $connect)
    {
        return parent::__construct($connect);
    }

    #[Override]
    public function find(int $id): ?User
    {
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email, 
            u.hash_password, u.created_at, u.updated_at, 
            u.img_profil, u.`grant` FROM `user` AS u 
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
    public function findAll(): array
    {
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email, 
            u.img_profil FROM `user` AS u ORDER BY u.id_user
            SQL;
        return $this->connect
            ->query($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    #[Override]
    public function create(Entity $entity): ?User
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('UserRepository attend une entité User.');
        }

        $sql = <<<SQL
            INSERT INTO `user`(firstname, lastname, email, hash_password, created_at,
            img_profil, `grant`) VALUES (?,?,?,?,?,?,?)
            SQL;

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

            $entity->setId((int) $this->connect->lastInsertId());
            return $entity;
        } catch (\PDOException $e) {
            // Laisser le service distinguer échec et succès sans afficher une erreur SQL.
            return null;
        }
    }

    public function findByEmail(string $email): ?User
    {
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email,
                   u.hash_password, u.created_at, u.updated_at,
                   u.img_profil, u.`grant`
            FROM `user` AS u
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
}
