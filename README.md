# MySQL Backup Library

Biblioteca PHP 7.4+ para realizar backups de banco de dados MySQL com suporte a exportação em SQL e ZIP, além de integração com Google Drive.

## 📋 Requisitos

- PHP >= 7.4
- Extensão PDO MySQL
- Extensão ZIP
- Composer

## 🚀 Instalação

```bash
composer require seu-usuario/mysql-backup
```

Ou adicione manualmente ao seu `composer.json`:

```json
{
    "require": {
        "seu-usuario/mysql-backup": "^1.0"
    }
}
```

## 📖 Uso Básico

### Backup em SQL

```php
use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;

$config = new BackupConfig([
    'host' => 'localhost',
    'database' => 'meu_banco',
    'username' => 'root',
    'password' => 'senha',
    'backup_path' => __DIR__ . '/backups'
]);

$manager = new BackupManager($config);
$arquivo = $manager->backupToSql();

echo "Backup criado: {$arquivo}";
```

### Backup em ZIP

```php
$arquivo = $manager->backupToZip();
echo "Backup ZIP criado: {$arquivo}";
```

### Backup com nome personalizado

```php
$arquivo = $manager->backupToZip('meu_backup_2025.zip');
```

## ☁️ Integração com Google Drive

### Preparação

1. Crie um projeto no [Google Cloud Console](https://console.cloud.google.com/)
2. Ative a API do Google Drive
3. Crie credenciais OAuth 2.0 ou Service Account
4. Baixe o arquivo JSON de credenciais

### Uso

```php
use MysqlBackup\Storage\GoogleDriveStorage;

$storage = new GoogleDriveStorage(
    '/caminho/para/credentials.json',
    'ID_DA_PASTA_NO_DRIVE' // opcional
);

// Integre com sua biblioteca do Google Drive
// $storage->setDriveService($seuServicoDrive);

$manager->setStorage($storage);
$arquivo = $manager->backupToZip(); // Salva localmente E no Drive
```

## ⚙️ Configurações

### Opções da BackupConfig

| Opção | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `host` | string | `localhost` | Host do banco de dados |
| `database` | string | `''` | Nome do banco de dados |
| `username` | string | `root` | Usuário do banco |
| `password` | string | `''` | Senha do banco |
| `port` | int | `3306` | Porta de conexão |
| `charset` | string | `utf8mb4` | Charset da conexão |
| `backup_path` | string | `sys_get_temp_dir()` | Caminho para salvar backups |

## 🏗️ Estrutura do Projeto

```
src/
├── BackupManager.php          # Gerenciador principal
├── Config/
│   └── BackupConfig.php       # Configurações
├── Interfaces/
│   ├── ExporterInterface.php  # Interface para exportadores
│   └── StorageInterface.php   # Interface para storage
├── Exporters/
│   ├── SqlExporter.php        # Exportador SQL
│   └── ZipExporter.php        # Exportador ZIP
└── Storage/
    └── GoogleDriveStorage.php # Storage Google Drive
```

## 🔧 Desenvolvimento

### Instalação para desenvolvimento

```bash
git clone https://github.com/seu-usuario/mysql-backup.git
cd mysql-backup
composer install
```

### Criando um novo Storage

Implemente a interface `StorageInterface`:

```php
namespace MysqlBackup\Interfaces;

interface StorageInterface
{
    public function upload(string $filePath, string $filename): bool;
}
```

### Criando um novo Exporter

Implemente a interface `ExporterInterface`:

```php
namespace MysqlBackup\Interfaces;

interface ExporterInterface
{
    public function export(string $filename): string;
}
```

## 📝 Exemplos Avançados

### Backup automático com Cron

```php
// backup-cron.php
require 'vendor/autoload.php';

$config = new MysqlBackup\Config\BackupConfig([
    'host' => getenv('DB_HOST'),
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'backup_path' => '/var/backups/mysql'
]);

$manager = new MysqlBackup\BackupManager($config);

try {
    $arquivo = $manager->backupToZip();
    echo date('Y-m-d H:i:s') . " - Backup realizado: {$arquivo}\n";
} catch (Exception $e) {
    echo date('Y-m-d H:i:s') . " - Erro: {$e->getMessage()}\n";
    exit(1);
}
```

Adicione ao crontab:
```bash
0 2 * * * /usr/bin/php /caminho/para/backup-cron.php >> /var/log/backup.log 2>&1
```

## 🐛 Tratamento de Erros

A biblioteca lança exceções `RuntimeException` em caso de erro:

```php
try {
    $arquivo = $manager->backupToSql();
} catch (\RuntimeException $e) {
    error_log("Erro no backup: " . $e->getMessage());
    // Implementar notificação, retry, etc.
}
```

## 📄 Licença

MIT License - veja o arquivo LICENSE para detalhes.

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, abra uma issue ou pull request.

## 🔗 Links Úteis

- [Documentação PHP PDO](https://www.php.net/manual/en/book.pdo.php)
- [Google Drive API PHP](https://developers.google.com/drive/api/v3/quickstart/php)
- [Composer](https://getcomposer.org/)