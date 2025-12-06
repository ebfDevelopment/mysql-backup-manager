<?php
/**
 * Limpeza de Backups Antigos
 * 
 * Funciona via CLI ou HTTP (curl)
 * 
 * CLI:  php cleanup-old-backups.php
 * CURL: curl https://seusite.com/cron/cleanup-old-backups.php?token=TOKEN
 * 
 * Crontab CLI:  0 4 * * * /usr/bin/php /caminho/para/cleanup-old-backups.php >> /var/log/backup-cleanup.log 2>&1
 * Crontab CURL: 0 4 * * * curl -s "https://seusite.com/cron/cleanup-old-backups.php?token=TOKEN" >> /var/log/backup-cleanup.log 2>&1
 */

require_once __DIR__ . '/CronBase.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Cron\CronBase;
use MysqlBackup\Storage\GoogleDriveStorage;

class CleanupOldBackups extends CronBase
{
    public function run()
    {
        $this->loadEnv(__DIR__ . '/../.env');
        
        $backupPath = getenv('BACKUP_PATH') ?: __DIR__ . '/../backups';
        $daysToKeep = (int) (getenv('KEEP_BACKUPS_DAYS') ?: 7);
        $cleanGoogleDrive = getenv('CLEANUP_GDRIVE') === 'true';
        
        $this->log('=== Limpeza de Backups Iniciada ===');
        $this->log("Política: Manter últimos {$daysToKeep} dias");
        
        // === LIMPEZA LOCAL ===
        $this->log('');
        $this->log('--- Limpeza Local ---');
        
        $totalDeleted = 0;
        $totalFreed = 0;
        
        if (is_dir($backupPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($backupPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            $cutoffTime = time() - ($daysToKeep * 86400);
            
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['sql', 'zip'])) {
                    $fileTime = $file->getMTime();
                    
                    if ($fileTime < $cutoffTime) {
                        $age = round((time() - $fileTime) / 86400);
                        $size = $file->getSize();
                        $sizeMB = round($size / 1024 / 1024, 2);
                        
                        $this->log("Removendo: {$file->getFilename()} ({$age} dias, {$sizeMB} MB)");
                        
                        if (unlink($file->getRealPath())) {
                            $totalDeleted++;
                            $totalFreed += $size;
                        }
                    }
                }
            }
        }
        
        $totalFreedMB = round($totalFreed / 1024 / 1024, 2);
        $this->log("✓ Arquivos removidos: {$totalDeleted}");
        $this->log("✓ Espaço liberado: {$totalFreedMB} MB");
        
        $result = [
            'local' => [
                'deleted' => $totalDeleted,
                'freed_mb' => $totalFreedMB
            ]
        ];
        
        // === LIMPEZA GOOGLE DRIVE (OPCIONAL) ===
        if ($cleanGoogleDrive && file_exists(__DIR__ . '/../credentials.json')) {
            $this->log('');
            $this->log('--- Limpeza Google Drive ---');
            
            try {
                $storage = new GoogleDriveStorage(
                    __DIR__ . '/../credentials.json',
                    getenv('GDRIVE_FOLDER_ID') ?: null
                );
                
                $files = $storage->listFiles(100);
                
                $this->log('Total de arquivos no Drive: ' . count($files));
                
                $driveDeleted = 0;
                $driveFreed = 0;
                $cutoffTime = time() - ($daysToKeep * 86400);
                
                foreach ($files as $file) {
                    $createdTime = strtotime($file->getCreatedTime());
                    
                    if ($createdTime < $cutoffTime) {
                        $age = round((time() - $createdTime) / 86400);
                        $sizeMB = round($file->getSize() / 1024 / 1024, 2);
                        
                        $this->log("Removendo do Drive: {$file->getName()} ({$age} dias, {$sizeMB} MB)");
                        
                        if ($storage->deleteFile($file->getId())) {
                            $driveDeleted++;
                            $driveFreed += $file->getSize();
                        }
                    }
                }
                
                $driveFreedMB = round($driveFreed / 1024 / 1024, 2);
                $this->log("✓ Arquivos removidos do Drive: {$driveDeleted}");
                $this->log("✓ Espaço liberado no Drive: {$driveFreedMB} MB");
                
                $result['gdrive'] = [
                    'deleted' => $driveDeleted,
                    'freed_mb' => $driveFreedMB
                ];
                
            } catch (Exception $e) {
                $this->log("✗ Erro na limpeza do Drive: {$e->getMessage()}");
                $result['gdrive'] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->log('');
        $this->log('=== Limpeza Concluída ===');
        
        $this->setSuccess(true, $result);
        $this->finish();
    }
}

// Executa
$cleanup = new CleanupOldBackups();
$cleanup->run();