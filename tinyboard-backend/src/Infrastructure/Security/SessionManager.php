<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class SessionManager
{
    private Connection $connection;
    private int $ttlHours;

    public function __construct(Connection $connection, \App\Application\Settings\SettingsInterface $settings)
    {
        $this->connection = $connection;
        $security = $settings->get('security');
        $this->ttlHours = (int) ($security['sessionTtlHours'] ?? 720);
    }

    public function create(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expires = $now->modify('+' . $this->ttlHours . ' hours');

        $stmt = $this->connection->pdo()->prepare('INSERT INTO sessions (token, user_id, expires_at, created_at) VALUES (:token, :user_id, :expires_at, :created_at)');
        if (!$stmt->execute([
            'token' => $token,
            'user_id' => $userId,
            'expires_at' => $expires->format('c'),
            'created_at' => $now->format('c'),
        ])) {
            throw new RuntimeException('Failed to create session.');
        }

        return $token;
    }

    public function validate(string $token): ?int
    {
        $stmt = $this->connection->pdo()->prepare('SELECT user_id, expires_at FROM sessions WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $expiresAt = new DateTimeImmutable($row['expires_at']);
        if ($expiresAt < new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            $this->revoke($token);
            return null;
        }

        return (int) $row['user_id'];
    }

    public function revoke(string $token): void
    {
        $stmt = $this->connection->pdo()->prepare('DELETE FROM sessions WHERE token = :token');
        $stmt->execute(['token' => $token]);
    }
}
