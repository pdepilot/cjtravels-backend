<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Migrator
{
    protected Database $db;
    protected string $migrationsPath;

    public function __construct(?Database $db = null, ?string $migrationsPath = null)
    {
        $this->db = $db ?: Database::getInstance();
        $baseDir = App::getInstance()->getBaseDir();
        $this->migrationsPath = $migrationsPath ?: ($baseDir . 'database' . DIRECTORY_SEPARATOR . 'migrations');
    }

    public function initTable(): void
    {
        $driver = $this->db->getDriver();
        if ($driver === 'mysql') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) UNIQUE NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) UNIQUE NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );";
        }
        $this->db->statement($sql);
    }

    public function getExecuted(): array
    {
        $this->initTable();
        $rows = $this->db->select("SELECT migration FROM migrations ORDER BY id ASC");
        return array_column($rows, 'migration');
    }

    public function run(): array
    {
        $this->initTable();
        $executed = $this->getExecuted();
        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.sql');
        sort($files);

        $ran = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $executed, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            $this->executeSqlScript($sql);

            $this->db->insert('migrations', [
                'migration' => $filename,
            ]);

            $ran[] = $filename;
        }

        return $ran;
    }

    protected function executeSqlScript(string $script): void
    {
        $pdo = $this->db->getPdo();
        $driver = $this->db->getDriver();

        if ($driver === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Split statements by semicolon
        $statements = array_filter(
            array_map('trim', explode(';', $script)),
            fn($stmt) => !empty($stmt)
        );

        foreach ($statements as $stmt) {
            // Strip comment lines
            $lines = explode("\n", str_replace("\r", "", $stmt));
            $cleanLines = array_filter($lines, fn($line) => !str_starts_with(trim($line), '--'));
            $cleaned = trim(implode("\n", $cleanLines));

            if (empty($cleaned)) {
                continue;
            }

            if ($driver === 'mysql') {
                // Adapt SQLite syntax to MySQL
                $cleaned = preg_replace('/\bINTEGER PRIMARY KEY AUTOINCREMENT\b/i', 'INT AUTO_INCREMENT PRIMARY KEY', $cleaned);
                $cleaned = preg_replace('/\bAUTOINCREMENT\b/i', 'AUTO_INCREMENT', $cleaned);

                // Handle CREATE INDEX IF NOT EXISTS
                if (preg_match('/CREATE\s+INDEX\s+(?:IF\s+NOT\s+EXISTS\s+)?([a-zA-Z0-9_]+)\s+ON\s+([a-zA-Z0-9_]+)\s*\((.+?)\)/i', $cleaned, $matches)) {
                    $indexName = $matches[1];
                    $tableName = $matches[2];
                    $columns = $matches[3];

                    try {
                        $checkStmt = $pdo->prepare("SHOW INDEX FROM `{$tableName}` WHERE Key_name = :key");
                        $checkStmt->execute([':key' => $indexName]);
                        if ($checkStmt->fetch()) {
                            continue; // Index exists, skip
                        }
                        $pdo->exec("CREATE INDEX `{$indexName}` ON `{$tableName}` ({$columns})");
                    } catch (PDOException $e) {
                        continue;
                    }
                    continue;
                }
            }

            try {
                $pdo->exec($cleaned);
            } catch (PDOException $e) {
                if ($e->getCode() === '42S01' || str_contains($e->getMessage(), 'already exists')) {
                    continue;
                }
                if ($driver === 'mysql') {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
                }
                throw $e;
            }
        }

        if ($driver === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
