<?php
/**
 * Backup Diário Automático
 * 
 * Funciona via CLI ou HTTP (curl)
 * 
 * CLI:  php daily-backup.php
 * CURL: curl https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN_SEGURO
 * 
 * Crontab CLI:  0 2 * * * /usr/bin/php /caminho/para/daily-backup.php >> /var/log/backup.log 2>&1
 * Crontab CURL: 0 2 * * * curl -s "https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN" >> /var/log/backup.log 2>&1
 */

// =============================================
// SEGURANÇA: Validação de Acesso
// =============================================

// Define o token de segurança (altere isso!)
define('CRON_TOKEN', getenv('CRON_TOKEN') ?: 'mude_este_token_123456');

// Detecta se está rodando via CLI ou HTTP
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Rodando via HTTP - requer token
    $requestToken = $_GET['token'] ?? '';
    
    if ($requestToken !== CRON_TOKEN) {
        http_response_code(403);
        die(json_encode([
            'success' => false,
            'error' => 'Token inválido ou ausente'
        ]));
    }
    
    // Define tipo de resposta
    header('Content-Type: application/json');
}

// =============================================
// Carrega dependências
// =============================================

require_once __DIR__ . '/../vendor/autoload.php';

use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;
use MysqlBackup\Storage\GoogleDriveStorage;

// Carrega variáveis de ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }
}

// =============================================
// Configurações
// =============================================

$config = new BackupConfig([
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_NAME') ?: 'production',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'backup_path' => getenv('BACKUP_PATH') ?: __DIR__ . '/../backups/daily'
]);

// =============================================
// Função de log que funciona em ambos os modos
// =============================================

$output = [];

function logMessage($message, $isCLI = true) {
    global $output;
    $logPrefix = '[' . date('Y-m-d H:i:s') . ']';
    $fullMessage = "{$logPrefix} {$message}";
    
    if ($isCLI) {
        echo $fullMessage . "\n";
    }
    
    $output[] = $message;
}

// =============================================
// Execução do Backup
// =============================================

logMessage('=== Backup Diário Iniciado ===', $isCLI);

$result = [
    'success' => false,
    'timestamp' => date('Y-m-d H:i:s'),
    'mode' => $isCLI ? 'CLI' : 'HTTP',
    'messages' => []
];

try {
    $manager = new BackupManager($config);
    
    // Configurar Google Drive (opcional)
    if (file_exists(__DIR__ . '/../credentials.json')) {
        $storage = new GoogleDriveStorage(
            __DIR__ . '/../credentials.json',
            getenv('GDRIVE_FOLDER_ID') ?: null
        );
        $manager->setStorage($storage);
        logMessage('Google Drive configurado', $isCLI);
    }
    
    // Cria backup
    $startTime = microtime(true);
    $arquivo = $manager->backupToZip();
    $duration = round(microtime(true) - $startTime, 2);
    
    // Informações do arquivo
    $filesize = filesize($arquivo);
    $filesizeMB = round($filesize / 1024 / 1024, 2);
    
    logMessage("✓ Backup criado: " . basename($arquivo), $isCLI);
    logMessage("✓ Tamanho: {$filesizeMB} MB", $isCLI);
    logMessage("✓ Tempo: {$duration}s", $isCLI);
    logMessage("✓ Local: {$arquivo}", $isCLI);
    
    $result['success'] = true;
    $result['file'] = basename($arquivo);
    $result['size_mb'] = $filesizeMB;
    $result['duration'] = $duration;
    $result['path'] = $arquivo;
    
    // Envia notificação de sucesso (opcional)
    if (function_exists('mail') && getenv('NOTIFY_EMAIL')) {
        $to = getenv('NOTIFY_EMAIL');
        $subject = "Backup Diário - Sucesso";
        $message = "Backup realizado com sucesso!\n\n";
        $message .= "Arquivo: " . basename($arquivo) . "\n";
        $message .= "Tamanho: {$filesizeMB} MB\n";
        $message .= "Data: " . date('d/m/Y H:i:s') . "\n";
        
        mail($to, $subject, $message);
    }
    
    logMessage('=== Backup Concluído com Sucesso ===', $isCLI);
    
} catch (Exception $e) {
    logMessage("✗ ERRO: {$e->getMessage()}", $isCLI);
    logMessage('=== Backup Falhou ===', $isCLI);
    
    $result['error'] = $e->getMessage();
    
    // Envia notificação de erro (opcional)
    if (function_exists('mail') && getenv('NOTIFY_EMAIL')) {
        $to = getenv('NOTIFY_EMAIL');
        $subject = "Backup Diário - ERRO";
        $message = "Falha no backup!\n\n";
        $message .= "Erro: {$e->getMessage()}\n";
        $message .= "Data: " . date('d/m/Y H:i:s') . "\n";
        
        mail($to, $subject, $message);
    }
}

// =============================================
// Resposta
// =============================================

$result['messages'] = $output;

if (!$isCLI) {
    // Resposta JSON para HTTP
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo "\n";
    exit($result['success'] ? 0 : 1);
}