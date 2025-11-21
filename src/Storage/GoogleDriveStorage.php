<?php

namespace MysqlBackup\Storage;

use MysqlBackup\Interfaces\StorageInterface;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class GoogleDriveStorage implements StorageInterface
{
    private string $credentialsPath;
    private ?string $folderId;
    private ?Google_Service_Drive $driveService = null;

    public function __construct(string $credentialsPath, ?string $folderId = null)
    {
        $this->credentialsPath = $credentialsPath;
        $this->folderId = $folderId;
        $this->initialize();
    }

    private function initialize(): void
    {
        if (!class_exists('Google_Client')) {
            throw new \RuntimeException(
                'Google API Client não está instalado. ' .
                'Execute: composer require google/apiclient:^2.15'
            );
        }

        if (!file_exists($this->credentialsPath)) {
            throw new \RuntimeException(
                'Arquivo de credenciais não encontrado: ' . $this->credentialsPath
            );
        }

        try {
            $client = new Google_Client();
            $client->setApplicationName('MySQL Backup');
            $client->setScopes([Google_Service_Drive::DRIVE_FILE]);
            $client->setAuthConfig($this->credentialsPath);
            $client->setAccessType('offline');

            // Tenta usar token salvo
            $tokenPath = $this->getTokenPath();
            if (file_exists($tokenPath)) {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $client->setAccessToken($accessToken);
            }

            // Verifica se o token expirou
            if ($client->isAccessTokenExpired()) {
                // Tenta renovar com refresh token
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    $this->saveToken($client->getAccessToken());
                } else {
                    // Precisa de autenticação manual
                    throw new \RuntimeException(
                        'Token expirado. Execute authenticate.php para autenticar novamente.'
                    );
                }
            }

            $this->driveService = new Google_Service_Drive($client);

        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Erro ao inicializar Google Drive: ' . $e->getMessage()
            );
        }
    }

    public function upload(string $filePath, string $filename): bool
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Arquivo não encontrado: ' . $filePath);
        }

        if ($this->driveService === null) {
            throw new \RuntimeException('Serviço do Google Drive não inicializado');
        }

        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $filename
            ]);

            // Se foi especificada uma pasta, adiciona aos parents
            if ($this->folderId !== null) {
                $fileMetadata->setParents([$this->folderId]);
            }

            $content = file_get_contents($filePath);
            $mimeType = $this->getMimeType($filePath);

            $file = $this->driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, size, createdTime'
            ]);

            if ($file->id !== null) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Erro ao fazer upload para Google Drive: ' . $e->getMessage()
            );
        }
    }

    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'sql' => 'application/sql',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    private function getTokenPath(): string
    {
        $dir = dirname($this->credentialsPath);
        return $dir . '/token.json';
    }

    private function saveToken(array $token): void
    {
        $tokenPath = $this->getTokenPath();
        file_put_contents($tokenPath, json_encode($token));
    }

    public function getCredentialsPath(): string
    {
        return $this->credentialsPath;
    }

    public function getFolderId(): ?string
    {
        return $this->folderId;
    }

    /**
     * Lista arquivos na pasta do Drive
     */
    public function listFiles(int $maxResults = 10): array
    {
        if ($this->driveService === null) {
            throw new \RuntimeException('Serviço do Google Drive não inicializado');
        }

        $optParams = [
            'pageSize' => $maxResults,
            'fields' => 'files(id, name, size, createdTime, mimeType)',
            'orderBy' => 'createdTime desc'
        ];

        if ($this->folderId !== null) {
            $optParams['q'] = "'{$this->folderId}' in parents and trashed=false";
        }

        $results = $this->driveService->files->listFiles($optParams);
        return $results->getFiles();
    }

    /**
     * Remove um arquivo do Drive
     */
    public function deleteFile(string $fileId): bool
    {
        if ($this->driveService === null) {
            throw new \RuntimeException('Serviço do Google Drive não inicializado');
        }

        try {
            $this->driveService->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Erro ao deletar arquivo do Google Drive: ' . $e->getMessage()
            );
        }
    }
}