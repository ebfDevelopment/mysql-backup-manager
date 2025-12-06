<?php
/**
 * Backup de Múltiplos Bancos de Dados
 * 
 * Funciona via CLI ou HTTP (curl)
 * 
 * CLI:  php multiple-databases-backup.php
 * CURL: curl https://seusite.com/cron/multiple-databases-backup.php?token=TOKEN
 * 
 * Crontab CLI:  0 1 * * * /usr/bin/php /caminho/para/multiple-databases-backup.php >> /var/log/backup-multiple.log 2>&1
 * Crontab CURL: 0 1 * * * curl -s "https://seusite.com/cron/multiple-databases-backup.php?token=TOKEN" >> /var/log/backup-multiple.log 2>&1
 */

require_once __DIR__ . '/CronBase.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Cron\CronBase;
use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Storage\GoogleDriveStorage;

class MultipleDatabasesBackup extends CronBase
{
    public function run()
    {
        $this->loadEnv(__DIR__ . '/../.env');
        
        // Lista de bancos para fazer backup
        $databases = [
            'production_db',
            'analytics_db',
            'logs_db',
            // Adicione mais bancos aqui
        ];
        
        $host = getenv('DB_HOST') ?: 'localhost';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';
        $basePath = getenv('BACKUP_PATH') ?: __DIR__ . '/../backups/multiple';
        
        // Google Drive (opcional)
        $googleStorage = null;
        if (file_exists(__DIR__ . '/../credentials.json')) {
            $googleStorage = new GoogleDriveStorage(
                __DIR__ . '/../credentials.json',
                getenv('GDRIVE_FOLDER_ID') ?: null
            );
        }
        
        $this->log('=== Backup de Múltiplos Bancos Iniciado ===');
        $this->log('Total de bancos: ' . count($databases));
        
        $successCount = 0;
        $failCount = 0;
        $totalSize = 0;
        $startTime = microtime(true);
        $results = [];
        
        foreach ($databases as $database) {
            $this->log('--------------------------------------------------');
            $this->log("Processando: {$database}");
            
            try {
                $config = new BackupConfig([
                    'host' => $host,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'backup_path' => $basePath . '/' . $database
                ]);
                
                $manager = new BackupManager($config);
                
                if ($googleStorage) {
                    $manager->setStorage($googleStorage);
                }
                
                $dbStartTime = microtime(true);
                $arquivo = $manager->backupToZip();
                $dbDuration = round(microtime(true) - $dbStartTime, 2);
                
                $filesize = filesize($arquivo);
                $filesizeMB = round($filesize / 1024 / 1024, 2);
                $totalSize += $filesize;
                
                $this->log("✓ Sucesso: {$database}");
                $this->log("  - Arquivo: " . basename($arquivo));
                $this->log("  - Tamanho: {$filesizeMB} MB");
                $this->log("  - Tempo: {$dbDuration}s");
                
                $results[] = [
                    'database' => $database,
                    'success' => true,
                    'file' => basename($arquivo),
                    'size_mb' => $filesizeMB,
                    'duration' => $dbDuration
                ];
                
                $successCount++;
                
            } catch (Exception $e) {
                $this->log("✗ Erro: {$database}");
                $this->log("  - Mensagem: {$e->getMessage()}");
                
                $results[] = [
                    'database' => $database,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                $failCount++;
            }
        }
        
        $totalDuration = round(microtime(true) - $startTime, 2);
        $totalSizeMB = round($totalSize / 1024 / 1024, 2);
        
        $this->log('');
        $this->log('=== Resumo ===');
        $this->log('Total processado: ' . count($databases) . ' banco(s)');
        $this->log("Sucesso: {$successCount}");
        $this->log("Falhas: {$failCount}");
        $this->log("Tamanho total: {$totalSizeMB} MB");
        $this->log("Tempo total: {$totalDuration}s");
        $this->log('=== Backup Múltiplo Concluído ===');
        
        $this->setSuccess($failCount === 0, [
            'total' => count($databases),
            'success' => $successCount,
            'failed' => $failCount,
            'total_size_mb' => $totalSizeMB,
            'duration' => $totalDuration,
            'results' => $results
        ]);
        
        $this->finish();
    }
}

// Executa
$backup = new MultipleDatabasesBackup();
$backup->run();