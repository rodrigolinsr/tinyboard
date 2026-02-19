<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Application\Settings\SettingsInterface;
use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private PDO $pdo;

    public function __construct(SettingsInterface $settings)
    {
        $dbSettings = $settings->get('db');
        if (!isset($dbSettings['database'])) {
            throw new RuntimeException('Database configuration missing.');
        }

        $dsn = 'sqlite:' . $dbSettings['database'];
        try {
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to connect to database: ' . $exception->getMessage());
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
