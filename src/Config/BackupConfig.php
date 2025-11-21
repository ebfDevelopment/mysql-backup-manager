<?php

namespace MysqlBackup\Config;

class BackupConfig
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private int $port;
    private string $charset;
    private string $backupPath;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? 'localhost';
        $this->database = $config['database'] ?? '';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';
        $this->port = $config['port'] ?? 3306;
        $this->charset = $config['charset'] ?? 'utf8mb4';
        $this->backupPath = $config['backup_path'] ?? sys_get_temp_dir();
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getBackupPath(): string
    {
        return $this->backupPath;
    }

    public function getDsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->database,
            $this->charset
        );
    }
}