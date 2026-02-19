<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;

class BoardRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM boards WHERE user_id = :user_id ORDER BY updated_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $name, ?string $description): array
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');
        $stmt = $this->connection->pdo()->prepare('INSERT INTO boards (user_id, name, description, created_at, updated_at) VALUES (:user_id, :name, :description, :created_at, :updated_at)');
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->connection->pdo()->lastInsertId(), $userId);
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM boards WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, int $userId, string $name, ?string $description): ?array
    {
        $stmt = $this->connection->pdo()->prepare('UPDATE boards SET name = :name, description = :description, updated_at = :updated_at WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'updated_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c'),
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $this->findById($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->connection->pdo()->prepare('DELETE FROM boards WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
