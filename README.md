# MySQL Backup Manager
Biblioteca PHP moderna para backup de bancos de dados MySQL com suporte a múltiplos formatos e integração com Google Drive.

## ✨ Características

- 🗄️ **Backup completo** (estrutura + dados)
- 📦 **Múltiplos formatos** (SQL, ZIP)
- ☁️ **Google Drive** integrado (opcional)
- 📤 **HTTP POST** para envio remoto
- 🔔 **Webhooks** para notificações em tempo real
- 🔧 **Modular e extensível**
- ⚡ **Fácil de usar**
- 🧪 **Testado** e documentado

## 📋 Requisitos

- PHP >= 7.4
- Extensão PDO MySQL
- Extensão ZIP
- Composer

## 🚀 Instalação

```bash
composer require edifonttes/mysql-backup-manager
```

Ou adicione manualmente ao seu `composer.json`:

```json
{
    "require": {
        "edifonttes/mysql-backup-manager": "^1.0"
    }
}
```
### Com Google Drive (opcional):
```bash
composer require google/apiclient:^2.15
```

## 💡 Uso Básico

### Backup SQL:

```php
<?php
require 'vendor/autoload.php';

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

// Backup em SQL
$arquivo = $manager->backupToSql();
echo "Backup criado: {$arquivo}";
```

### Backup ZIP:

```php
// Backup compactado
$arquivo = $manager->backupToZip();
```

### Com Google Drive:

```php
use MysqlBackup\Storage\GoogleDriveStorage;

$storage = new GoogleDriveStorage(
    __DIR__ . '/credentials.json',
    'ID_DA_PASTA' // opcional
);

$manager->setStorage($storage);
$arquivo = $manager->backupToZip(); // Local + Google Drive
```

### Com HTTP POST (envio remoto):

```php
use MysqlBackup\Storage\HttpPostStorage;

$storage = new HttpPostStorage(
    'https://seu-servidor.com/backup/receive-backup.php',
    [],
    ['token' => 'seu_token_seguro']
);

$manager->setStorage($storage);
$arquivo = $manager->backupToZip(); // Local + envio remoto
```

### Com Webhooks (notificações):

```php
use MysqlBackup\Notifications\WebhookNotifier;

$webhook = new WebhookNotifier([
    'https://seu-servidor.com/webhook'
]);

try {
    $arquivo = $manager->backupToZip();
    
    // Notifica sucesso
    $webhook->notifySuccess([
        'file' => basename($arquivo),
        'size_mb' => 12.5
    ]);
} catch (Exception $e) {
    // Notifica falha
    $webhook->notifyFailure($e->getMessage());
}
```

## 📚 Exemplos Avançados

Esta biblioteca inclui exemplos prontos para produção em `docs/examples/`:

### 📂 Copie para sua aplicação:

```bash
# Scripts de Cron (CLI e CURL)
cp -r vendor/seu-usuario/mysql-backup/docs/examples/cron ./

# Sistema de Webhooks
cp -r vendor/seu-usuario/mysql-backup/docs/examples/webhooks ./

# Configuração
cp vendor/seu-usuario/mysql-backup/docs/examples/config/.env.example ./.env

# Scripts utilitários
cp vendor/seu-usuario/mysql-backup/docs/examples/scripts/* ./
```

### 🎯 O que está incluído:

#### **Cron Jobs:**
- ✅ Backup diário automático
- ✅ Backup semanal com rotação
- ✅ Múltiplos bancos de dados
- ✅ Limpeza de backups antigos
- ✅ Suporte CLI e CURL

#### **Webhooks Internos:**
- ✅ Registro automático de eventos
- ✅ Formatos: JSON, CSV, TXT
- ✅ Visualizador web
- ✅ API para integração

#### **Scripts Úteis:**
- ✅ Instalador de cron jobs
- ✅ Testes automatizados
- ✅ Configuração facilitada

## 📖 Documentação Completa

Guias detalhados disponíveis em `docs/guides/`:

- 📘 [Instalação](docs/guides/INSTALLATION.md)
- 📗 [Uso Básico](docs/guides/BASIC_USAGE.md)
- 📙 [Configuração de Cron](docs/guides/CRON_SETUP.md)
- 📕 [Cron via CURL](docs/guides/CURL_SETUP.md)
- 📤 [Envio via HTTP POST](docs/guides/HTTP_POST_SETUP.md)
- 🔔 [Webhooks (Notificações)](docs/guides/WEBHOOK_NOTIFICATIONS.md)
- 📔 [Webhooks Internos (Logs)](docs/guides/WEBHOOK_SETUP.md)
- 📓 [Testes](docs/guides/TESTING.md)
- 📒 [Troubleshooting](docs/guides/TROUBLESHOOTING.md)

## ⚙️ Configuração

### BackupConfig:

```php
$config = new BackupConfig([
    'host' => 'localhost',      // Host do banco
    'database' => 'database',   // Nome do banco
    'username' => 'root',       // Usuário
    'password' => 'senha',      // Senha
    'port' => 3306,            // Porta (padrão: 3306)
    'charset' => 'utf8mb4',    // Charset (padrão: utf8mb4)
    'backup_path' => '/path'   // Onde salvar
]);
```

## 🔧 Opções Avançadas

### Backup com nome personalizado:

```php
$manager->backupToSql('meu_backup_custom.sql');
$manager->backupToZip('meu_backup_custom.zip');
```

### Listar arquivos no Google Drive:

```php
$files = $storage->listFiles(10);
foreach ($files as $file) {
    echo $file->getName() . "\n";
}
```

### Deletar arquivo do Drive:

```php
$storage->deleteFile('ID_DO_ARQUIVO');
```

## 🎯 Casos de Uso

### Backup Agendado (Cron):

```bash
# Todo dia às 2h
0 2 * * * /usr/bin/php /caminho/cron/daily-backup.php
```

### Backup via CURL (Hospedagem compartilhada):

```bash
# CPanel, Plesk, etc
0 2 * * * curl -s "https://seu-site.com/cron/daily-backup.php?token=TOKEN"
```

### Múltiplos Bancos:

```php
$databases = ['db1', 'db2', 'db3'];

foreach ($databases as $db) {
    $config->setDatabase($db);
    $manager = new BackupManager($config);
    $manager->backupToZip();
}
```

## 🧪 Testes

```bash
# Instale dependências de dev
composer install --dev

# Execute testes
vendor/bin/phpunit

# Com coverage
vendor/bin/phpunit --coverage-html coverage
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -am 'Add nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Abra um Pull Request

## 📝 Changelog

Veja [CHANGELOG.md](CHANGELOG.md) para histórico de versões.

### Últimas versões:

- **1.2.0** - Integração para envio remoto (HTTP POST), Webhooks para notificações em tempo real
- **1.1.0** - Integração Google Drive, exemplos de cron
- **1.0.0** - Release inicial com backup SQL e ZIP

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🙏 Agradecimentos

- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
- Comunidade PHP

## 🌟 Star History

Se esta biblioteca foi útil, considere dar uma ⭐ no GitHub!

---

**Desenvolvido com ❤️ para a comunidade PHP**