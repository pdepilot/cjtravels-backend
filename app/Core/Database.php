<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    protected static ?self $instance = null;
    protected ?PDO $pdo = null;
    protected string $connectionName;

    public function __construct(?string $connectionName = null)
    {
        $this->connectionName = $connectionName ?: (config('database.default', 'mysql'));
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function getDriver(): string
    {
        $connections = config('database.connections', []);
        return $connections[$this->connectionName]['driver'] ?? 'mysql';
    }

    protected function connect(): void
    {
        $connections = config('database.connections', []);
        $config = $connections[$this->connectionName] ?? null;

        if (!$config) {
            throw new RuntimeException("Database connection [{$this->connectionName}] is not configured.");
        }

        $driver = $config['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dbPath = $config['database'];
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (!file_exists($dbPath)) {
                    touch($dbPath);
                }

                $this->pdo = new PDO("sqlite:" . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            } elseif ($driver === 'mysql') {
                $host = $config['host'] ?? '127.0.0.1';
                $port = $config['port'] ?? '3306';
                $database = $config['database'] ?? 'travelbookings';
                $username = $config['username'] ?? 'root';
                $password = $config['password'] ?? '';
                $charset = $config['charset'] ?? 'utf8mb4';

                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
                    $this->pdo = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (PDOException $e) {
                    // If database does not exist (error 1049), auto-create it
                    if ($e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                        $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
                        $tempPdo = new PDO($serverDsn, $username, $password, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        ]);
                        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $tempPdo = null;

                        $this->pdo = new PDO($dsn, $username, $password, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                    } else {
                        throw $e;
                    }
                }
            } else {
                throw new RuntimeException("Unsupported database driver [{$driver}].");
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function pdo(): PDO
    {
        return self::getInstance()->getPdo();
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): int|string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $col => $val) {
            $params[':' . $col] = $val;
        }

        $this->query($sql, $params);
        return $this->getPdo()->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $col => $val) {
            $setClauses[] = "{$col} = :set_{$col}";
            $params[":set_{$col}"] = $val;
        }

        foreach ($whereParams as $k => $v) {
            $key = str_starts_with($k, ':') ? $k : ':' . $k;
            $params[$key] = $v;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            $where
        );

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $params = [];
        foreach ($whereParams as $k => $v) {
            $key = str_starts_with($k, ':') ? $k : ':' . $k;
            $params[$key] = $v;
        }

        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function statement(string $sql): bool
    {
        return $this->getPdo()->exec($sql) !== false;
    }
}
