<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;

class CommentRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function listByTask(int $taskId): array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM comments WHERE task_id = :task_id ORDER BY created_at ASC');
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function create(int $taskId, string $body): array
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');
        $stmt = $this->connection->pdo()->prepare('INSERT INTO comments (task_id, body, created_at) VALUES (:task_id, :body, :created_at)');
        $stmt->execute([
            'task_id' => $taskId,
            'body' => $body,
            'created_at' => $now,
        ]);

        return $this->findById((int) $this->connection->pdo()->lastInsertId(), $taskId);
    }

    public function findById(int $id, int $taskId): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM comments WHERE id = :id AND task_id = :task_id');
        $stmt->execute(['id' => $id, 'task_id' => $taskId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id, int $taskId): bool
    {
        $stmt = $this->connection->pdo()->prepare('DELETE FROM comments WHERE id = :id AND task_id = :task_id');
        $stmt->execute(['id' => $id, 'task_id' => $taskId]);
        return $stmt->rowCount() > 0;
    }
}
