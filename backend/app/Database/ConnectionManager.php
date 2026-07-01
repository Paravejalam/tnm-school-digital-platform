<?php

namespace App\Database;

use PDO;
use PDOException;

class ConnectionManager
{
    private static ?self $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function connect(array $config): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->connection = $pdo;
            return $pdo;
        } catch (PDOException $exception) {
            throw new PDOException('Database connection failed: ' . $exception->getMessage(), (int) $exception->getCode());
        }
    }

    public function isConnected(): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        try {
            $this->connection->getAttribute(PDO::ATTR_SERVER_INFO);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }
}
