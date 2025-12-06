<?php
/**
 * Endpoint para receber backups via HTTP POST
 * 
 * Coloque este arquivo no servidor que vai RECEBER os backups
 * URL: https://servidor-destino.com/receive-backup.php
 */

// ============================================
// CONFIGURAÇÕES
// ============================================

// Token de segurança (MUDE ISSO!)
define('BACKUP_TOKEN', 'seu_token_seguro_aqui_123456');

// Pasta onde salvar os backups recebidos
define('BACKUP_STORAGE_PATH', __DIR__ . '/received-backups');

// IPs permitidos (opcional - deixe vazio para permitir todos)
$allowedIPs = [
    // '192.168.1.100',
    // '10.0.0.50',
];

// ============================================
// VALIDAÇÕES
// ============================================

header('Content-Type: application/json');

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use POST.'
    ]));
}

// Verifica token
$token = $_POST['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($token !== BACKUP_TOKEN) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Token inválido'
    ]));
}

// Verifica IP (se configurado)
if (!empty($allowedIPs)) {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($clientIP, $allowedIPs)) {
        http_response_code(403);
        die(json_encode([
            'success' => false,
            'error' => 'IP não autorizado'
        ]));
    }
}

// Verifica se arquivo foi enviado
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'Nenhum arquivo enviado ou erro no upload'
    ]));
}

// ============================================
// PROCESSAMENTO
// ============================================

try {
    // Cria pasta se não existir
    if (!is_dir(BACKUP_STORAGE_PATH)) {
        mkdir(BACKUP_STORAGE_PATH, 0755, true);
    }
    
    // Dados do arquivo
    $uploadedFile = $_FILES['file'];
    $filename = basename($_POST['filename'] ?? $uploadedFile['name']);
    $timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
    $size = $_POST['size'] ?? $uploadedFile['size'];
    
    // Valida nome do arquivo
    if (!preg_match('/^[\w\-\.]+\.(sql|zip)$/i', $filename)) {
        throw new Exception('Nome de arquivo inválido');
    }
    
    // Gera nome único se já existir
    $destinationPath = BACKUP_STORAGE_PATH . '/' . $filename;
    if (file_exists($destinationPath)) {
        $info = pathinfo($filename);
        $filename = $info['filename'] . '_' . time() . '.' . $info['extension'];
        $destinationPath = BACKUP_STORAGE_PATH . '/' . $filename;
    }
    
    // Move o arquivo
    if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
        throw new Exception('Erro ao salvar arquivo');
    }
    
    // Verifica integridade (tamanho)
    $savedSize = filesize($destinationPath);
    if ($savedSize !== (int)$size) {
        unlink($destinationPath);
        throw new Exception('Tamanho do arquivo não corresponde. Arquivo corrompido.');
    }
    
    // Log do recebimento
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'filename' => $filename,
        'size' => $savedSize,
        'size_mb' => round($savedSize / 1024 / 1024, 2),
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'original_timestamp' => $timestamp
    ];
    
    file_put_contents(
        BACKUP_STORAGE_PATH . '/receive.log',
        json_encode($logEntry) . "\n",
        FILE_APPEND
    );
    
    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Backup recebido com sucesso',
        'filename' => $filename,
        'size' => $savedSize,
        'size_mb' => round($savedSize / 1024 / 1024, 2),
        'path' => $destinationPath,
        'received_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}