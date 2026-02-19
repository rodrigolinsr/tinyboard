<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;

class UserRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $stmt = $this->connection->pdo()->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (:name, :email, :password_hash, :created_at)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'created_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c'),
        ]);

        return (int) $this->connection->pdo()->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findWithPasswordById(int $id): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT id, name, email, password_hash, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateName(int $id, string $name): ?array
    {
        $stmt = $this->connection->pdo()->prepare('UPDATE users SET name = :name WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return $this->findById($id);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->connection->pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }
}
