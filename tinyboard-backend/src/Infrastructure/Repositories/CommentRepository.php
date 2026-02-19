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
        $stmt = $this->connection->pdo()->prepare(
            'SELECT comments.*, users.name AS user_name, api_keys.label AS api_key_label
             FROM comments
             LEFT JOIN users ON users.id = comments.user_id
             LEFT JOIN api_keys ON api_keys.id = comments.api_key_id
             WHERE comments.task_id = :task_id
             ORDER BY comments.created_at ASC'
        );
        $stmt->execute(['task_id' => $taskId]);
        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => $this->withAuthorDisplay($row), $rows);
    }

    public function create(int $taskId, string $body, ?int $userId, ?int $apiKeyId): array
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');
        $stmt = $this->connection->pdo()->prepare(
            'INSERT INTO comments (task_id, user_id, api_key_id, body, created_at)
             VALUES (:task_id, :user_id, :api_key_id, :body, :created_at)'
        );
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'api_key_id' => $apiKeyId,
            'body' => $body,
            'created_at' => $now,
        ]);

        return $this->findById((int) $this->connection->pdo()->lastInsertId(), $taskId);
    }

    public function findById(int $id, int $taskId): ?array
    {
        $stmt = $this->connection->pdo()->prepare(
            'SELECT comments.*, users.name AS user_name, api_keys.label AS api_key_label
             FROM comments
             LEFT JOIN users ON users.id = comments.user_id
             LEFT JOIN api_keys ON api_keys.id = comments.api_key_id
             WHERE comments.id = :id AND comments.task_id = :task_id'
        );
        $stmt->execute(['id' => $id, 'task_id' => $taskId]);
        $row = $stmt->fetch();
        return $row ? $this->withAuthorDisplay($row) : null;
    }

    private function withAuthorDisplay(array $row): array
    {
        $authorName = $row['user_name'] ?? null;
        $apiKeyLabel = $row['api_key_label'] ?? null;
        if ($authorName) {
            $row['author_display'] = $authorName . ' commented';
        } elseif ($apiKeyLabel) {
            $row['author_display'] = $apiKeyLabel . ' commented';
        } else {
            $row['author_display'] = 'Unknown commented';
        }

        unset($row['user_name'], $row['api_key_label']);
        return $row;
    }

    public function delete(int $id, int $taskId): bool
    {
        $stmt = $this->connection->pdo()->prepare('DELETE FROM comments WHERE id = :id AND task_id = :task_id');
        $stmt->execute(['id' => $id, 'task_id' => $taskId]);
        return $stmt->rowCount() > 0;
    }
}
