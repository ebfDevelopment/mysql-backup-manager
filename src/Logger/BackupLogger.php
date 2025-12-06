<?php

namespace MysqlBackup\Logger;

/**
 * Logger para gerar relatórios de backup
 */
class BackupLogger
{
    private string $logPath;
    private string $logFile;
    
    public function __construct(string $logPath = null)
    {
        $this->logPath = $logPath ?: __DIR__ . '/../../logs/reports';
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        $this->logFile = $this->logPath . '/backup-reports.json';
    }
    
    /**
     * Registra um backup
     */
    public function log(array $data): bool
    {
        $entry = [
            'id' => uniqid('bkp_', true),
            'timestamp' => date('Y-m-d H:i:s'),
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'success' => $data['success'] ?? false,
            'type' => $data['type'] ?? 'manual', // manual, cron, api
            'format' => $data['format'] ?? 'zip', // sql, zip
            'database' => $data['database'] ?? '',
            'filename' => $data['filename'] ?? '',
            'size_bytes' => $data['size_bytes'] ?? 0,
            'size_mb' => round(($data['size_bytes'] ?? 0) / 1024 / 1024, 2),
            'duration' => $data['duration'] ?? 0,
            'storage' => $data['storage'] ?? 'local', // local, gdrive, http
            'error' => $data['error'] ?? null,
            'user' => $data['user'] ?? 'system',
            'ip' => $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'CLI'),
            'metadata' => $data['metadata'] ?? []
        ];
        
        $logs = $this->readAll();
        $logs[] = $entry;
        
        return file_put_contents(
            $this->logFile,
            json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }
    
    /**
     * Lê todos os logs
     */
    public function readAll(): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = file_get_contents($this->logFile);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * Filtra logs por período
     */
    public function getByPeriod(string $startDate, string $endDate): array
    {
        $logs = $this->readAll();
        
        return array_filter($logs, function($log) use ($startDate, $endDate) {
            return $log['date'] >= $startDate && $log['date'] <= $endDate;
        });
    }
    
    /**
     * Filtra logs por tipo
     */
    public function getByType(string $type): array
    {
        $logs = $this->readAll();
        
        return array_filter($logs, function($log) use ($type) {
            return $log['type'] === $type;
        });
    }
    
    /**
     * Filtra apenas sucessos
     */
    public function getSuccessful(): array
    {
        $logs = $this->readAll();
        
        return array_filter($logs, function($log) {
            return $log['success'] === true;
        });
    }
    
    /**
     * Filtra apenas falhas
     */
    public function getFailed(): array
    {
        $logs = $this->readAll();
        
        return array_filter($logs, function($log) {
            return $log['success'] === false;
        });
    }
    
    /**
     * Gera relatório resumido
     */
    public function getReport(string $startDate = null, string $endDate = null): array
    {
        if ($startDate && $endDate) {
            $logs = $this->getByPeriod($startDate, $endDate);
        } else {
            $logs = $this->readAll();
        }
        
        $total = count($logs);
        $success = count(array_filter($logs, fn($l) => $l['success']));
        $failed = $total - $success;
        
        $totalSize = array_sum(array_column($logs, 'size_bytes'));
        $avgDuration = $total > 0 ? array_sum(array_column($logs, 'duration')) / $total : 0;
        
        // Agrupa por tipo
        $byType = [];
        foreach ($logs as $log) {
            $type = $log['type'];
            if (!isset($byType[$type])) {
                $byType[$type] = 0;
            }
            $byType[$type]++;
        }
        
        // Agrupa por storage
        $byStorage = [];
        foreach ($logs as $log) {
            $storage = $log['storage'];
            if (!isset($byStorage[$storage])) {
                $byStorage[$storage] = 0;
            }
            $byStorage[$storage]++;
        }
        
        return [
            'period' => [
                'start' => $startDate ?: ($logs[0]['date'] ?? date('Y-m-d')),
                'end' => $endDate ?: date('Y-m-d')
            ],
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'total_size_gb' => round($totalSize / 1024 / 1024 / 1024, 2),
            'avg_duration' => round($avgDuration, 2),
            'by_type' => $byType,
            'by_storage' => $byStorage,
            'last_backup' => end($logs) ?: null
        ];
    }
    
    /**
     * Obtém últimos N backups
     */
    public function getLatest(int $limit = 10): array
    {
        $logs = $this->readAll();
        return array_slice($logs, -$limit);
    }
    
    /**
     * Busca por ID
     */
    public function getById(string $id): ?array
    {
        $logs = $this->readAll();
        
        foreach ($logs as $log) {
            if ($log['id'] === $id) {
                return $log;
            }
        }
        
        return null;
    }
    
    /**
     * Limpa logs antigos
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        $logs = $this->readAll();
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        
        $filtered = array_filter($logs, function($log) use ($cutoffDate) {
            return $log['date'] >= $cutoffDate;
        });
        
        $removed = count($logs) - count($filtered);
        
        file_put_contents(
            $this->logFile,
            json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        return $removed;
    }
    
    /**
     * Exporta relatório para CSV
     */
    public function exportToCsv(string $outputPath = null): string
    {
        $outputPath = $outputPath ?: $this->logPath . '/report_' . date('Y-m-d') . '.csv';
        $logs = $this->readAll();
        
        $handle = fopen($outputPath, 'w');
        
        // Cabeçalho
        fputcsv($handle, [
            'ID', 'Data', 'Hora', 'Sucesso', 'Tipo', 'Formato',
            'Banco', 'Arquivo', 'Tamanho (MB)', 'Duração (s)',
            'Storage', 'Erro', 'Usuário', 'IP'
        ]);
        
        // Dados
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log['id'],
                $log['date'],
                $log['time'],
                $log['success'] ? 'Sim' : 'Não',
                $log['type'],
                $log['format'],
                $log['database'],
                $log['filename'],
                $log['size_mb'],
                $log['duration'],
                $log['storage'],
                $log['error'] ?? '',
                $log['user'],
                $log['ip']
            ]);
        }
        
        fclose($handle);
        
        return $outputPath;
    }
}