<?php
/**
 * Sistema de Webhook Interno
 * Registra eventos de backup em arquivos estruturados
 */

namespace App\Webhooks;

class WebhookLogger
{
    private string $logPath;
    private string $format; // 'txt', 'json', 'csv'
    
    public function __construct(string $logPath = null, string $format = 'json')
    {
        $this->logPath = $logPath ?: __DIR__ . '/../logs/webhooks';
        $this->format = $format;
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Registra evento de backup
     */
    public function log(string $event, array $data): bool
    {
        $timestamp = date('Y-m-d H:i:s');
        $date = date('Y-m-d');
        
        $entry = [
            'timestamp' => $timestamp,
            'event' => $event,
            'data' => $data
        ];
        
        switch ($this->format) {
            case 'json':
                return $this->logJson($entry, $date);
                
            case 'csv':
                return $this->logCsv($entry, $date);
                
            case 'txt':
            default:
                return $this->logTxt($entry, $date);
        }
    }
    
    /**
     * Log em formato TXT (legível)
     */
    private function logTxt(array $entry, string $date): bool
    {
        $filename = $this->logPath . "/backup_{$date}.txt";
        
        $content = str_repeat('=', 70) . "\n";
        $content .= "[{$entry['timestamp']}] {$entry['event']}\n";
        $content .= str_repeat('-', 70) . "\n";
        
        foreach ($entry['data'] as $key => $value) {
            if (is_array($value)) {
                $content .= "{$key}:\n";
                foreach ($value as $k => $v) {
                    $content .= "  - {$k}: {$v}\n";
                }
            } else {
                $content .= "{$key}: {$value}\n";
            }
        }
        
        $content .= "\n";
        
        return file_put_contents($filename, $content, FILE_APPEND) !== false;
    }
    
    /**
     * Log em formato JSON (estruturado)
     */
    private function logJson(array $entry, string $date): bool
    {
        $filename = $this->logPath . "/backup_{$date}.json";
        
        $logs = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $logs = json_decode($content, true) ?: [];
        }
        
        $logs[] = $entry;
        
        return file_put_contents(
            $filename,
            json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }
    
    /**
     * Log em formato CSV (planilha)
     */
    private function logCsv(array $entry, string $date): bool
    {
        $filename = $this->logPath . "/backup_{$date}.csv";
        
        $isNew = !file_exists($filename);
        $handle = fopen($filename, 'a');
        
        if (!$handle) {
            return false;
        }
        
        // Cabeçalho (apenas se arquivo novo)
        if ($isNew) {
            fputcsv($handle, [
                'timestamp',
                'event',
                'success',
                'database',
                'file',
                'size_mb',
                'duration',
                'error'
            ]);
        }
        
        // Extrai dados principais
        $data = $entry['data'];
        
        fputcsv($handle, [
            $entry['timestamp'],
            $entry['event'],
            $data['success'] ?? '',
            $data['database'] ?? '',
            $data['file'] ?? '',
            $data['size_mb'] ?? '',
            $data['duration'] ?? '',
            $data['error'] ?? ''
        ]);
        
        fclose($handle);
        return true;
    }
    
    /**
     * Lê logs de um período
     */
    public function read(string $date = null, int $limit = 100): array
    {
        $date = $date ?: date('Y-m-d');
        $filename = $this->logPath . "/backup_{$date}.json";
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $content = file_get_contents($filename);
        $logs = json_decode($content, true) ?: [];
        
        return array_slice($logs, -$limit);
    }
    
    /**
     * Lista arquivos de log disponíveis
     */
    public function listLogs(): array
    {
        $files = glob($this->logPath . "/backup_*.{$this->format}");
        
        return array_map(function($file) {
            return [
                'date' => basename($file, ".{$this->format}"),
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }, $files);
    }
    
    /**
     * Limpa logs antigos
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        $files = glob($this->logPath . "/backup_*.{$this->format}");
        $cutoff = time() - ($daysToKeep * 86400);
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}