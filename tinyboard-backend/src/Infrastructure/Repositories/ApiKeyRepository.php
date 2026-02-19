<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;

class ApiKeyRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT id, label, last_used_at, created_at, revoked_at FROM api_keys WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function revoke(int $id, int $userId): bool
    {
        $stmt = $this->connection->pdo()->prepare('UPDATE api_keys SET revoked_at = :revoked_at WHERE id = :id AND user_id = :user_id AND revoked_at IS NULL');
        $stmt->execute([
            'revoked_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c'),
            'id' => $id,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
