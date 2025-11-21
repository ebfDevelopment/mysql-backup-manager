<?php

require_once __DIR__ . '/vendor/autoload.php';

use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Storage\GoogleDriveStorage;

// Configuração do banco de dados
$config = new BackupConfig([
    'host' => 'localhost',
    'database' => 'meu_banco',
    'username' => 'root',
    'password' => 'senha',
    'port' => 3306,
    'backup_path' => __DIR__ . '/backups'
]);

// Cria o gerenciador de backup
$backupManager = new BackupManager($config);

try {
    // ============================================
    // Exemplo 1: Backup apenas em SQL
    // ============================================
    echo "Criando backup SQL...\n";
    $sqlFile = $backupManager->backupToSql();
    echo "Backup SQL criado: {$sqlFile}\n\n";

    // ============================================
    // Exemplo 2: Backup em ZIP
    // ============================================
    echo "Criando backup ZIP...\n";
    $zipFile = $backupManager->backupToZip();
    echo "Backup ZIP criado: {$zipFile}\n\n";

    // ============================================
    // Exemplo 3: Backup com nome personalizado
    // ============================================
    echo "Criando backup com nome personalizado...\n";
    $customFile = $backupManager->backupToZip('meu_backup_custom.zip');
    echo "Backup personalizado criado: {$customFile}\n\n";

    // ============================================
    // Exemplo 4: Backup com envio para Google Drive
    // ============================================
    echo "Criando backup e enviando para Google Drive...\n";
    
    // Configura o storage do Google Drive
    $googleDriveStorage = new GoogleDriveStorage(
        __DIR__ . '/credentials.json', // Caminho para seu JSON de credenciais
        'ID_DA_PASTA_NO_DRIVE' // ID da pasta (opcional)
    );

    // Quando você tiver a biblioteca do Google Drive:
    // $googleDriveStorage->setDriveService($seuServicoDrive);

    $backupManager->setStorage($googleDriveStorage);
    
    // Este backup será salvo localmente E enviado para o Google Drive
    $driveFile = $backupManager->backupToZip('backup_para_drive.zip');
    echo "Backup criado e enviado para Google Drive: {$driveFile}\n\n";

} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

// ============================================
// Exemplo 5: Uso mais completo com tratamento
// ============================================

function fazerBackupCompleto(): void
{
    $config = new BackupConfig([
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_NAME') ?: 'meu_banco',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'backup_path' => __DIR__ . '/backups/' . date('Y-m-d')
    ]);

    $manager = new BackupManager($config);

    try {
        // Cria backup em ZIP
        $arquivo = $manager->backupToZip();
        echo "✓ Backup realizado com sucesso: " . basename($arquivo) . "\n";
        
        // Verifica o tamanho do arquivo
        $tamanho = filesize($arquivo);
        echo "✓ Tamanho do backup: " . number_format($tamanho / 1024 / 1024, 2) . " MB\n";
        
        return;
    } catch (\Exception $e) {
        echo "✗ Erro ao realizar backup: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Descomente para executar:
// fazerBackupCompleto();