<?php

namespace MysqlBackup;

use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Exporters\SqlExporter;
use MysqlBackup\Exporters\ZipExporter;
use MysqlBackup\Interfaces\ExporterInterface;
use MysqlBackup\Interfaces\StorageInterface;

class BackupManager
{
    private BackupConfig $config;
    private ?StorageInterface $storage = null;

    public function __construct(BackupConfig $config)
    {
        $this->config = $config;
    }

    public function setStorage(StorageInterface $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

    public function backupToSql(string $filename = null): string
    {
        $exporter = new SqlExporter($this->config);
        return $this->executeBackup($exporter, $filename ?? $this->generateFilename('sql'));
    }

    public function backupToZip(string $filename = null): string
    {
        $exporter = new ZipExporter($this->config);
        return $this->executeBackup($exporter, $filename ?? $this->generateFilename('zip'));
    }

    private function executeBackup(ExporterInterface $exporter, string $filename): string
    {
        $filePath = $exporter->export($filename);

        if ($this->storage !== null) {
            $this->storage->upload($filePath, $filename);
        }

        return $filePath;
    }

    private function generateFilename(string $extension): string
    {
        $database = $this->config->getDatabase();
        $timestamp = date('Y-m-d_H-i-s');
        return sprintf('%s_%s.%s', $database, $timestamp, $extension);
    }

    public function getConfig(): BackupConfig
    {
        return $this->config;
    }
}