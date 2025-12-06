# 🔔 Guia de Webhooks Internos

Sistema de registro de eventos de backup em arquivos estruturados.

## 📋 O que é?

O sistema de webhook interno registra automaticamente todos os eventos de backup em arquivos locais estruturados (TXT, JSON ou CSV), permitindo:

- ✅ Histórico completo de backups
- ✅ Análise de sucessos/falhas
- ✅ Auditoria de operações
- ✅ Integração com sistemas externos
- ✅ Visualização web dos logs

## ⚙️ Configuração

### **1. Configure no .env:**

```env
# Habilita webhook
WEBHOOK_ENABLED=true

# Formato dos logs: txt, json, csv
WEBHOOK_FORMAT=json

# Caminho para salvar logs
WEBHOOK_LOG_PATH=./logs/webhooks

# Permite visualizador público (padrão: false)
WEBHOOK_VIEWER_PUBLIC=false
```

### **2. Crie a pasta de logs:**

```bash
mkdir -p logs/webhooks
chmod 755 logs/webhooks
```

## 📄 Formatos Disponíveis

### **JSON (Recomendado)**

Estruturado, fácil de integrar com outras aplicações.

```json
[
  {
    "timestamp": "2025-01-15 14:30:00",
    "event": "daily_backup_success",
    "data": {
      "success": true,
      "file": "backup.zip",
      "size_mb": 12.45,
      "duration": 3.21
    }
  }
]
```

**Vantagens:**
- ✅ Estruturado
- ✅ Fácil parsear programaticamente
- ✅ Suporta dados complexos

### **TXT (Legível)**

Formato texto, fácil de ler diretamente.

```
======================================================================
[2025-01-15 14:30:00] daily_backup_success
----------------------------------------------------------------------
success: true
file: backup.zip
size_mb: 12.45
duration: 3.21
```

**Vantagens:**
- ✅ Legível por humanos
- ✅ Fácil buscar com grep
- ✅ Pequeno tamanho

### **CSV (Planilha)**

Formato compatível com Excel/Google Sheets.

```csv
timestamp,event,success,database,file,size_mb,duration,error
2025-01-15 14:30:00,daily_backup_success,true,production,backup.zip,12.45,3.21,
```

**Vantagens:**
- ✅ Importável em planilhas
- ✅ Fácil gerar relatórios
- ✅ Análise estatística

## 📊 Visualizador Web

### **Acesso:**

```
http://seusite.com/webhooks/view-logs.php
```

### **Recursos:**
- 📅 Filtrar por data
- 📈 Estatísticas (total, sucesso, falhas)
- 🔍 Detalhes de cada backup
- 🎨 Interface visual amigável

### **Segurança:**

Por padrão, apenas localhost pode acessar:

```php
$allowedIPs = ['127.0.0.1', '::1'];
```

**Para permitir outros IPs:**

Edite `webhooks/view-logs.php`:

```php
$allowedIPs = [
    '127.0.0.1',
    '::1',
    'SEU.IP.AQUI'
];
```

**Ou libere para todos (não recomendado):**

```env
WEBHOOK_VIEWER_PUBLIC=true
```

## 🔧 Uso Programático

### **Ler logs via PHP:**

```php
require_once 'webhooks/WebhookLogger.php';

use App\Webhooks\WebhookLogger;

$logger = new WebhookLogger();

// Lê logs de hoje
$logs = $logger->read(date('Y-m-d'));

// Lê logs de uma data específica
$logs = $logger->read('2025-01-15');

// Lê últimos 50 eventos
$logs = $logger->read(date('Y-m-d'), 50);

foreach ($logs as $log) {
    echo $log['timestamp'] . ' - ' . $log['event'] . "\n";
    print_r($log['data']);
}
```

### **Listar arquivos de log:**

```php
$logger = new WebhookLogger();
$files = $logger->listLogs();

foreach ($files as $file) {
    echo "{$file['date']} - {$file['size']} bytes\n";
}
```

### **Limpeza automática:**

```php
$logger = new WebhookLogger();

// Remove logs com mais de 30 dias
$deleted = $logger->cleanup(30);

echo "Removidos: {$deleted} arquivos\n";
```

## 📝 Estrutura dos Eventos

### **Eventos registrados automaticamente:**

| Evento | Quando |
|--------|--------|
| `daily_backup_success` | Backup diário bem-sucedido |
| `daily_backup_failed` | Backup diário falhou |
| `weekly_backup_success` | Backup semanal bem-sucedido |
| `weekly_backup_failed` | Backup semanal falhou |
| `multiple_databases_backup_success` | Múltiplos bancos OK |
| `multiple_databases_backup_failed` | Múltiplos bancos com erro |
| `cleanup_old_backups_success` | Limpeza concluída |
| `cleanup_old_backups_failed` | Limpeza falhou |

### **Dados incluídos:**

```json
{
  "timestamp": "2025-01-15 14:30:00",
  "event": "daily_backup_success",
  "data": {
    "success": true,
    "mode": "HTTP",
    "database": "production",
    "file": "backup_2025-01-15.zip",
    "size_mb": 12.45,
    "duration": 3.21,
    "path": "/var/backups/backup.zip",
    "messages": [
      "=== Backup Iniciado ===",
      "✓ Backup criado",
      "=== Concluído ==="
    ]
  }
}
```

## 🔄 Integração com Sistemas Externos

### **Processar logs em tempo real:**

```bash
# Monitora novos eventos (Linux)
tail -f logs/webhooks/backup_$(date +%Y-%m-%d).json | while read line; do
    echo "Novo evento: $line"
    # Processar/enviar para outro sistema
done
```

### **Webhook para API externa:**

Crie `webhooks/ExternalWebhook.php`:

```php
<?php
namespace App\Webhooks;

class ExternalWebhook
{
    public function send(string $url, array $data): bool
    {
        $payload = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $statusCode >= 200 && $statusCode < 300;
    }
}
```

Use em `CronBase.php`:

```php
protected function finish(): void
{
    // ... código existente ...
    
    // Envia para API externa
    if (getenv('EXTERNAL_WEBHOOK_URL')) {
        $webhook = new \App\Webhooks\ExternalWebhook();
        $webhook->send(
            getenv('EXTERNAL_WEBHOOK_URL'),
            $this->result
        );
    }
    
    $this->finish();
}
```

## 📈 Análise e Relatórios

### **Contar sucessos/falhas:**

```bash
# Total de backups bem-sucedidos hoje
grep -c "success.*true" logs/webhooks/backup_$(date +%Y-%m-%d).json

# Total de falhas
grep -c "success.*false" logs/webhooks/backup_$(date +%Y-%m-%d).json
```

### **Gerar relatório mensal:**

```php
<?php
require_once 'webhooks/WebhookLogger.php';

$logger = new App\Webhooks\WebhookLogger();

$year = date('Y');
$month = date('m');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$stats = [
    'total' => 0,
    'success' => 0,
    'failed' => 0
];

for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = sprintf('%s-%s-%02d', $year, $month, $day);
    $logs = $logger->read($date);
    
    foreach ($logs as $log) {
        $stats['total']++;
        if ($log['data']['success'] ?? false) {
            $stats['success']++;
        } else {
            $stats['failed']++;
        }
    }
}

echo "Relatório de {$month}/{$year}:\n";
echo "Total: {$stats['total']}\n";
echo "Sucesso: {$stats['success']}\n";
echo "Falhas: {$stats['failed']}\n";
echo "Taxa de sucesso: " . round(($stats['success'] / $stats['total']) * 100, 2) . "%\n";
```

## 🧹 Manutenção

### **Limpeza automática via cron:**

```bash
# Todo dia à meia-noite, remove logs com mais de 30 dias
0 0 * * * php -r "require 'webhooks/WebhookLogger.php'; \$l = new App\Webhooks\WebhookLogger(); \$l->cleanup(30);"
```

### **Rotação de logs:**

```bash
# Compacta logs antigos
find logs/webhooks -name "*.json" -mtime +7 -exec gzip {} \;
```

## 🎯 Boas Práticas

1. ✅ **Formato JSON** - Mais versátil para integração
2. ✅ **Limpeza regular** - Evita logs muito grandes
3. ✅ **Backup dos logs** - Logs também são importantes!
4. ✅ **Monitore falhas** - Configure alertas para eventos de erro
5. ✅ **Restrinja acesso** - Visualizador apenas para IPs confiáveis
6. ✅ **Compacte antigos** - Use gzip para economizar espaço

## 🔐 Segurança

- 🔒 Logs ficam fora do document root (recomendado)
- 🔒 Visualizador restrito por IP
- 🔒 Não expõe senhas ou dados sensíveis
- 🔒 Logs não acessíveis diretamente via HTTP

## 💡 Dicas

**Integração com Slack/Discord/Telegram:**

Crie um script que lê os logs e envia notificações:

```php
// webhooks/NotifyOnFailure.php
$logs = $logger->read(date('Y-m-d'));

foreach ($logs as $log) {
    if (!($log['data']['success'] ?? true)) {
        // Enviar notificação
        sendToSlack($log);
    }
}
```

**Dashboard personalizado:**

Use os logs JSON para criar seu próprio dashboard com:
- Chart.js para gráficos
- Filtros avançados
- Exportação de relatórios

---

**Pronto!** Sistema de webhook completo e funcional! 🎉