<?php

namespace MysqlBackup\Exporters;

use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Interfaces\ExporterInterface;
use ZipArchive;

class ZipExporter implements ExporterInterface
{
    private BackupConfig $config;
    private SqlExporter $sqlExporter;

    public function __construct(BackupConfig $config)
    {
        $this->config = $config;
        $this->sqlExporter = new SqlExporter($config);
    }

    public function export(string $filename): string
    {
        $backupPath = $this->config->getBackupPath();
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Gera o arquivo SQL temporário
        $sqlFilename = str_replace('.zip', '.sql', $filename);
        $sqlPath = $this->sqlExporter->export($sqlFilename);

        // Cria o arquivo ZIP
        $zipPath = rtrim($backupPath, '/') . '/' . $filename;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Erro ao criar arquivo ZIP');
        }

        $zip->addFile($sqlPath, basename($sqlPath));
        $zip->close();

        // Remove o arquivo SQL temporário
        unlink($sqlPath);

        return $zipPath;
    }
}