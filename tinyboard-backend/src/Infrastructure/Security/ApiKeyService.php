<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use DateTimeZone;

class ApiKeyService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function generate(int $userId, string $label): array
    {
        $plain = bin2hex(random_bytes(24));
        $hash = hash('sha256', $plain);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $stmt = $this->connection->pdo()->prepare('INSERT INTO api_keys (user_id, label, key_hash, created_at) VALUES (:user_id, :label, :key_hash, :created_at)');
        $stmt->execute([
            'user_id' => $userId,
            'label' => $label,
            'key_hash' => $hash,
            'created_at' => $now->format('c'),
        ]);

        return [
            'plain' => $plain,
            'id' => (int) $this->connection->pdo()->lastInsertId(),
            'label' => $label,
            'createdAt' => $now->format('c'),
        ];
    }

    public function findUserIdByKey(string $plainKey): ?int
    {
        $hash = hash('sha256', $plainKey);
        $stmt = $this->connection->pdo()->prepare('SELECT id, user_id FROM api_keys WHERE key_hash = :hash AND revoked_at IS NULL');
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $this->touchLastUsed((int) $row['id']);
        return (int) $row['user_id'];
    }

    private function touchLastUsed(int $id): void
    {
        $stmt = $this->connection->pdo()->prepare('UPDATE api_keys SET last_used_at = :last_used_at WHERE id = :id');
        $stmt->execute([
            'last_used_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c'),
            'id' => $id,
        ]);
    }
}
