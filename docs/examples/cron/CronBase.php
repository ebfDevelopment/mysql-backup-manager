<?php
/**
 * Classe Base para Scripts Cron
 * Gerencia autenticação CLI/HTTP, logging e webhooks
 */

namespace App\Cron;

require_once __DIR__ . '/../webhooks/WebhookLogger.php';

use App\Webhooks\WebhookLogger;

class CronBase
{
    protected bool $isCLI;
    protected array $output = [];
    protected array $result = [];
    protected ?WebhookLogger $webhook = null;
    
    public function __construct()
    {
        $this->isCLI = php_sapi_name() === 'cli';
        $this->authenticate();
        $this->initResult();
        $this->initWebhook();
    }
    
    /**
     * Valida autenticação
     */
    private function authenticate(): void
    {
        if ($this->isCLI) {
            return; // CLI sempre permitido
        }
        
        // HTTP requer token
        $cronToken = getenv('CRON_TOKEN') ?: 'mude_este_token_123456';
        $requestToken = $_GET['token'] ?? '';
        
        if ($requestToken !== $cronToken) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'error' => 'Token inválido ou ausente',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        }
        
        header('Content-Type: application/json');
    }
    
    /**
     * Inicializa resultado
     */
    private function initResult(): void
    {
        $this->result = [
            'success' => false,
            'timestamp' => date('Y-m-d H:i:s'),
            'mode' => $this->isCLI ? 'CLI' : 'HTTP',
            'messages' => []
        ];
    }
    
    /**
     * Inicializa webhook
     */
    private function initWebhook(): void
    {
        $webhookEnabled = getenv('WEBHOOK_ENABLED') !== 'false';
        
        if ($webhookEnabled) {
            $logPath = getenv('WEBHOOK_LOG_PATH') ?: __DIR__ . '/../logs/webhooks';
            $format = getenv('WEBHOOK_FORMAT') ?: 'json'; // txt, json, csv
            
            $this->webhook = new WebhookLogger($logPath, $format);
        }
    }
    
    /**
     * Loga mensagem
     */
    protected function log(string $message): void
    {
        $logPrefix = '[' . date('Y-m-d H:i:s') . ']';
        $fullMessage = "{$logPrefix} {$message}";
        
        if ($this->isCLI) {
            echo $fullMessage . "\n";
        }
        
        $this->output[] = $message;
    }
    
    /**
     * Define sucesso
     */
    protected function setSuccess(bool $success, array $data = []): void
    {
        $this->result['success'] = $success;
        $this->result = array_merge($this->result, $data);
    }
    
    /**
     * Define erro
     */
    protected function setError(string $error): void
    {
        $this->result['error'] = $error;
    }
    
    /**
     * Registra evento no webhook
     */
    protected function triggerWebhook(string $event, array $data = []): void
    {
        if ($this->webhook) {
            $webhookData = array_merge($this->result, $data);
            $this->webhook->log($event, $webhookData);
        }
    }
    
    /**
     * Finaliza e retorna resultado
     */
    protected function finish(): void
    {
        $this->result['messages'] = $this->output;
        
        // Registra no webhook
        $eventName = $this->getEventName();
        $this->triggerWebhook($eventName, $this->result);
        
        if (!$this->isCLI) {
            http_response_code($this->result['success'] ? 200 : 500);
            echo json_encode($this->result, JSON_PRETTY_PRINT);
        } else {
            echo "\n";
        }
        
        exit($this->result['success'] ? 0 : 1);
    }
    
    /**
     * Obtém nome do evento baseado na classe
     */
    private function getEventName(): string
    {
        $class = get_class($this);
        $name = basename(str_replace('\\', '/', $class));
        
        // Converte CamelCase para snake_case
        $event = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        
        return $this->result['success'] ? "{$event}_success" : "{$event}_failed";
    }
    
    /**
     * Carrega variáveis de ambiente
     */
    protected function loadEnv(string $path): void
    {
        if (file_exists($path)) {
            $env = parse_ini_file($path);
            foreach ($env as $key => $value) {
                putenv("{$key}={$value}");
            }
        }
    }
}