<?php
/**
 * Visualizador de Logs de Webhook
 * 
 * Acesse: http://seusite.com/webhooks/view-logs.php?date=2025-01-15
 */

require_once __DIR__ . '/WebhookLogger.php';

use App\Webhooks\WebhookLogger;

// Segurança básica (adicione autenticação se necessário)
$allowedIPs = ['127.0.0.1', '::1']; // Apenas localhost por padrão
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIP, $allowedIPs) && getenv('WEBHOOK_VIEWER_PUBLIC') !== 'true') {
    http_response_code(403);
    die('Acesso negado');
}

header('Content-Type: text/html; charset=utf-8');

$logger = new WebhookLogger();
$date = $_GET['date'] ?? date('Y-m-d');
$logs = $logger->read($date);
$availableLogs = $logger->listLogs();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Backup - <?= htmlspecialchars($date) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .filters label {
            font-weight: bold;
            margin-right: 10px;
        }
        .filters select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .log-entry {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .log-header {
            padding: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .log-header:hover {
            background: #f9f9f9;
        }
        .log-header.success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        .log-header.failed {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        .log-timestamp {
            font-weight: bold;
            color: #555;
        }
        .log-event {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .log-event.success {
            background: #4CAF50;
            color: white;
        }
        .log-event.failed {
            background: #f44336;
            color: white;
        }
        .log-details {
            display: none;
            padding: 15px;
            background: #fafafa;
            border-top: 1px solid #e0e0e0;
        }
        .log-details.active {
            display: block;
        }
        .log-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .log-details td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .log-details td:first-child {
            font-weight: bold;
            width: 150px;
            color: #666;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Logs de Backup - <?= htmlspecialchars($date) ?></h1>
        
        <div class="filters">
            <label for="date-select">Data:</label>
            <select id="date-select" onchange="location.href='?date='+this.value">
                <?php foreach (array_reverse($availableLogs) as $log): ?>
                    <?php 
                        $logDate = str_replace('backup_', '', $log['date']);
                        $selected = $logDate === $date ? 'selected' : '';
                    ?>
                    <option value="<?= $logDate ?>" <?= $selected ?>>
                        <?= $logDate ?> (<?= round($log['size']/1024, 2) ?> KB)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="empty">
                <p>Nenhum log encontrado para esta data.</p>
            </div>
        <?php else: ?>
            <?php
                $total = count($logs);
                $success = count(array_filter($logs, fn($l) => $l['data']['success'] ?? false));
                $failed = $total - $success;
            ?>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total de Backups</h3>
                    <div class="value"><?= $total ?></div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <h3>Sucesso</h3>
                    <div class="value"><?= $success ?></div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                    <h3>Falhas</h3>
                    <div class="value"><?= $failed ?></div>
                </div>
            </div>
            
            <?php foreach (array_reverse($logs) as $index => $log): ?>
                <?php
                    $isSuccess = $log['data']['success'] ?? false;
                    $statusClass = $isSuccess ? 'success' : 'failed';
                ?>
                <div class="log-entry">
                    <div class="log-header <?= $statusClass ?>" onclick="toggleDetails(<?= $index ?>)">
                        <span class="log-timestamp"><?= htmlspecialchars($log['timestamp']) ?></span>
                        <span class="log-event <?= $statusClass ?>">
                            <?= $isSuccess ? '✓' : '✗' ?> <?= htmlspecialchars($log['event']) ?>
                        </span>
                    </div>
                    <div class="log-details" id="details-<?= $index ?>">
                        <table>
                            <?php foreach ($log['data'] as $key => $value): ?>
                                <?php if ($key === 'messages') continue; ?>
                                <tr>
                                    <td><?= htmlspecialchars($key) ?>:</td>
                                    <td>
                                        <?php if (is_array($value)): ?>
                                            <pre><?= json_encode($value, JSON_PRETTY_PRINT) ?></pre>
                                        <?php else: ?>
                                            <?= htmlspecialchars($value) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($log['data']['messages'])): ?>
                                <tr>
                                    <td>Mensagens:</td>
                                    <td>
                                        <?php foreach ($log['data']['messages'] as $msg): ?>
                                            <div><?= htmlspecialchars($msg) ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleDetails(index) {
            const details = document.getElementById('details-' + index);
            details.classList.toggle('active');
        }
    </script>
</body>
</html>