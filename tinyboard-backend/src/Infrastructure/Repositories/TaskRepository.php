<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;

class TaskRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function listByBoard(int $boardId): array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM tasks WHERE board_id = :board_id ORDER BY status, position, updated_at DESC');
        $stmt->execute(['board_id' => $boardId]);
        return $stmt->fetchAll();
    }

    public function create(int $boardId, string $title, ?string $description, string $status, int $position): array
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');
        $stmt = $this->connection->pdo()->prepare('INSERT INTO tasks (board_id, title, description, status, position, created_at, updated_at) VALUES (:board_id, :title, :description, :status, :position, :created_at, :updated_at)');
        $stmt->execute([
            'board_id' => $boardId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->connection->pdo()->lastInsertId(), $boardId);
    }

    public function findById(int $id, int $boardId): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM tasks WHERE id = :id AND board_id = :board_id');
        $stmt->execute(['id' => $id, 'board_id' => $boardId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, int $boardId, array $data): ?array
    {
        $fields = [];
        $params = ['id' => $id, 'board_id' => $boardId];

        foreach (['title', 'description', 'status', 'position'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if ($fields === []) {
            return $this->findById($id, $boardId);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id AND board_id = :board_id';
        $stmt = $this->connection->pdo()->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id, $boardId);
    }

    public function delete(int $id, int $boardId): bool
    {
        $stmt = $this->connection->pdo()->prepare('DELETE FROM tasks WHERE id = :id AND board_id = :board_id');
        $stmt->execute(['id' => $id, 'board_id' => $boardId]);
        return $stmt->rowCount() > 0;
    }
}
