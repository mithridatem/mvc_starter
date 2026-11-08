-- Schema minimal d'authentification du profil API JWT.
-- Les tables metier sont ajoutees uniquement dans les projets applicatifs.
CREATE DATABASE IF NOT EXISTS `mvc_starter_api` CHARSET utf8mb4;
USE `mvc_starter_api`;

CREATE TABLE IF NOT EXISTS `user` (
    id_user INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(50) UNIQUE NOT NULL,
    hash_password VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at DATETIME,
    img_profil VARCHAR(200),
    `grant` VARCHAR(50)
);
