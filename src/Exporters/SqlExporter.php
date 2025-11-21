<?php

namespace MysqlBackup\Exporters;

use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Interfaces\ExporterInterface;
use PDO;
use PDOException;

class SqlExporter implements ExporterInterface
{
    private BackupConfig $config;
    private PDO $pdo;

    public function __construct(BackupConfig $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $this->pdo = new PDO(
                $this->config->getDsn(),
                $this->config->getUsername(),
                $this->config->getPassword(),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    public function export(string $filename): string
    {
        $backupPath = $this->config->getBackupPath();
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filePath = rtrim($backupPath, '/') . '/' . $filename;
        $sql = $this->generateSqlDump();

        if (file_put_contents($filePath, $sql) === false) {
            throw new \RuntimeException('Erro ao salvar arquivo de backup');
        }

        return $filePath;
    }

    private function generateSqlDump(): string
    {
        $sql = "-- MySQL Backup\n";
        $sql .= "-- Database: " . $this->config->getDatabase() . "\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        $tables = $this->getTables();

        foreach ($tables as $table) {
            $sql .= $this->getTableStructure($table);
            $sql .= $this->getTableData($table);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    private function getTables(): array
    {
        $stmt = $this->pdo->query('SHOW TABLES');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getTableStructure(string $table): string
    {
        $sql = "\n-- Structure for table `{$table}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

        $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= $row['Create Table'] . ";\n\n";

        return $sql;
    }

    private function getTableData(string $table): string
    {
        $sql = "-- Data for table `{$table}`\n";

        $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return $sql . "\n";
        }

        foreach ($rows as $row) {
            $values = array_map(function ($value) {
                if ($value === null) {
                    return 'NULL';
                }
                return $this->pdo->quote($value);
            }, array_values($row));

            $columns = array_map(function ($col) {
                return "`{$col}`";
            }, array_keys($row));

            $sql .= sprintf(
                "INSERT INTO `%s` (%s) VALUES (%s);\n",
                $table,
                implode(', ', $columns),
                implode(', ', $values)
            );
        }

        $sql .= "\n";
        return $sql;
    }
}