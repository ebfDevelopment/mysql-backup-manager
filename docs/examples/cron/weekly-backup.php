<?php
/**
 * Backup Semanal com Rotação
 * 
 * Funciona via CLI ou HTTP (curl)
 * 
 * CLI:  php weekly-backup.php
 * CURL: curl https://seusite.com/cron/weekly-backup.php?token=SEU_TOKEN
 * 
 * Crontab CLI:  0 3 * * 0 /usr/bin/php /caminho/para/weekly-backup.php >> /var/log/backup-weekly.log 2>&1
 * Crontab CURL: 0 3 * * 0 curl -s "https://seusite.com/cron/weekly-backup.php?token=TOKEN" >> /var/log/backup-weekly.log 2>&1
 */

require_once __DIR__ . '/CronBase.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Cron\CronBase;
use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Storage\GoogleDriveStorage;

class WeeklyBackup extends CronBase
{
    public function run()
    {
        $this->loadEnv(__DIR__ . '/../.env');
        
        $backupPath = getenv('BACKUP_PATH') ?: __DIR__ . '/../backups/weekly';
        $keepWeeks = (int) (getenv('KEEP_WEEKLY_BACKUPS') ?: 4);
        
        $config = new BackupConfig([
            'host' => getenv('DB_HOST') ?: 'localhost',
            'database' => getenv('DB_NAME') ?: 'production',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'backup_path' => $backupPath
        ]);
        
        $this->log('=== Backup Semanal Iniciado ===');
        $this->log("Política de retenção: {$keepWeeks} semanas");
        
        try {
            $manager = new BackupManager($config);
            
            // Google Drive (opcional)
            if (file_exists(__DIR__ . '/../credentials.json')) {
                $storage = new GoogleDriveStorage(
                    __DIR__ . '/../credentials.json',
                    getenv('GDRIVE_FOLDER_ID') ?: null
                );
                $manager->setStorage($storage);
                $this->log('Google Drive configurado');
            }
            
            // Cria backup com nome baseado na semana
            $weekNumber = date('W');
            $year = date('Y');
            $filename = "weekly_backup_W{$weekNumber}_{$year}.zip";
            
            $this->log("Criando backup: {$filename}");
            
            $startTime = microtime(true);
            $arquivo = $manager->backupToZip($filename);
            $duration = round(microtime(true) - $startTime, 2);
            
            $filesizeMB = round(filesize($arquivo) / 1024 / 1024, 2);
            
            $this->log("✓ Backup criado: {$filesizeMB} MB");
            $this->log("✓ Tempo: {$duration}s");
            
            // === ROTAÇÃO DE BACKUPS ===
            $this->log('Iniciando rotação de backups...');
            
            $files = glob($backupPath . '/weekly_backup_*.zip');
            
            // Ordena por data de modificação (mais recente primeiro)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $this->log('Total de backups encontrados: ' . count($files));
            
            // Remove backups antigos (mantém apenas $keepWeeks)
            $toDelete = array_slice($files, $keepWeeks);
            
            foreach ($toDelete as $oldFile) {
                $age = round((time() - filemtime($oldFile)) / 86400);
                $this->log("Removendo backup antigo: " . basename($oldFile) . " ({$age} dias)");
                unlink($oldFile);
            }
            
            $remaining = count(glob($backupPath . '/weekly_backup_*.zip'));
            $this->log("✓ Backups restantes: {$remaining}");
            
            $this->setSuccess(true, [
                'file' => $filename,
                'size_mb' => $filesizeMB,
                'duration' => $duration,
                'path' => $arquivo,
                'backups_kept' => $remaining,
                'backups_deleted' => count($toDelete)
            ]);
            
            $this->log('=== Backup Semanal Concluído ===');
            
        } catch (Exception $e) {
            $this->log("✗ ERRO: {$e->getMessage()}");
            $this->setError($e->getMessage());
            $this->log('=== Backup Semanal Falhou ===');
        }
        
        $this->finish();
    }
}

// Executa
$backup = new WeeklyBackup();
$backup->run();