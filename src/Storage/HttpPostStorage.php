<?php

namespace MysqlBackup\Storage;

use MysqlBackup\Interfaces\StorageInterface;

class HttpPostStorage implements StorageInterface
{
    private string $url;
    private array $headers;
    private array $extraData;
    private int $timeout;
    private bool $verifySSL;
    
    /**
     * @param string $url URL do endpoint que vai receber o backup
     * @param array $headers Headers HTTP adicionais (ex: Authorization)
     * @param array $extraData Dados extras para enviar junto com o arquivo
     * @param int $timeout Timeout em segundos (padrão: 300 = 5 minutos)
     * @param bool $verifySSL Verificar certificado SSL (padrão: true)
     */
    public function __construct(
        string $url,
        array $headers = [],
        array $extraData = [],
        int $timeout = 300,
        bool $verifySSL = true
    ) {
        $this->url = $url;
        $this->headers = $headers;
        $this->extraData = $extraData;
        $this->timeout = $timeout;
        $this->verifySSL = $verifySSL;
    }
    
    /**
     * Envia o arquivo via HTTP POST
     */
    public function upload(string $filePath, string $filename): bool
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Arquivo não encontrado: ' . $filePath);
        }
        
        // Prepara o arquivo
        $fileContent = file_get_contents($filePath);
        $fileSize = filesize($filePath);
        $mimeType = $this->getMimeType($filePath);
        
        // Cria o CURLFile
        if (class_exists('CURLFile')) {
            // PHP 5.5+
            $cfile = new \CURLFile($filePath, $mimeType, $filename);
        } else {
            // PHP 5.4 (fallback)
            $cfile = '@' . $filePath;
        }
        
        // Prepara dados do POST
        $postData = array_merge($this->extraData, [
            'file' => $cfile,
            'filename' => $filename,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Inicializa CURL
        $ch = curl_init($this->url);
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySSL ? 2 : 0);
        
        // Headers personalizados
        if (!empty($this->headers)) {
            $headerLines = [];
            foreach ($this->headers as $key => $value) {
                $headerLines[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }
        
        // Executa
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Verifica resultado
        if ($error) {
            throw new \RuntimeException("Erro CURL: {$error}");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException(
                "Erro HTTP {$httpCode}: {$response}"
            );
        }
        
        return true;
    }
    
    /**
     * Detecta MIME type do arquivo
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'sql' => 'application/sql',
            'zip' => 'application/zip',
            'gz' => 'application/gzip',
            'tar' => 'application/x-tar',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Retorna a URL configurada
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    
    /**
     * Retorna os headers configurados
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    /**
     * Retorna dados extras configurados
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }
}