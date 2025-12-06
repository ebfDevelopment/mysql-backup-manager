# 📦 Guia de Instalação

## Requisitos do Sistema

Antes de instalar, certifique-se de que seu ambiente atende aos requisitos:

### Obrigatórios:
- ✅ PHP >= 7.4
- ✅ Extensão PDO MySQL
- ✅ Extensão ZIP
- ✅ Composer
- ✅ MySQL/MariaDB

### Opcionais:
- ⚪ Google API Client (para Google Drive)
- ⚪ Acesso SSH (para cron via CLI)

## 🚀 Instalação da Biblioteca

### 1. Via Composer (Recomendado):

```bash
composer require seu-usuario/mysql-backup
```

### 2. Com Google Drive:

```bash
composer require seu-usuario/mysql-backup
composer require google/apiclient:^2.15
```

## 📂 Configuração Inicial na Aplicação

### 1. Copie os exemplos:

```bash
# Navegue até sua aplicação
cd /caminho/para/sua-aplicacao

# Copie scripts de cron
cp -r vendor/seu-usuario/mysql-backup/docs/examples/cron ./

# Copie webhooks
cp -r vendor/seu-usuario/mysql-backup/docs/examples/webhooks ./

# Copie configuração
cp vendor/seu-usuario/mysql-backup/docs/examples/config/.env.example ./.env
```

### 2. Configure o .env:

```bash
nano .env
```

```env
# Banco de Dados
DB_HOST=localhost
DB_NAME=seu_banco
DB_USER=seu_usuario
DB_PASS=sua_senha

# Backup
BACKUP_PATH=/var/backups/mysql

# Token de segurança (gere um aleatório)
CRON_TOKEN=seu_token_seguro_aqui
```

### 3. Crie pastas necessárias:

```bash
mkdir -p backups logs/webhooks
chmod 755 backups logs
```

### 4. Estrutura final:

```
sua-aplicacao/
├── vendor/
│   └── seu-usuario/mysql-backup/    ← Lib instalada
├── cron/                             ← Copiado dos exemplos
│   ├── CronBase.php
│   ├── daily-backup.php
│   ├── weekly-backup.php
│   ├── multiple-databases-backup.php
│   └── cleanup-old-backups.php
├── webhooks/                         ← Copiado dos exemplos
│   ├── WebhookLogger.php
│   └── view-logs.php
├── backups/                          ← Criado manualmente
├── logs/                             ← Criado manualmente
│   └── webhooks/
├── .env                              ← Configurado
└── composer.json
```

## ✅ Teste a Instalação

### 1. Teste básico:

```bash
php -r "require 'vendor/autoload.php'; echo 'Autoload OK!';"
```

### 2. Teste a biblioteca:

Crie `test-installation.php`:

```php
<?php
require 'vendor/autoload.php';

use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;

echo "Testando MySQL Backup Manager...\n";

$config = new BackupConfig([
    'host' => 'localhost',
    'database' => 'information_schema', // Banco padrão do MySQL
    'username' => 'root',
    'password' => '',
    'backup_path' => __DIR__ . '/backups'
]);

try {
    $manager = new BackupManager($config);
    echo "✅ Biblioteca instalada corretamente!\n";
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}
```

Execute:
```bash
php test-installation.php
```

## 🐛 Problemas Comuns

### "Class not found":
```bash
composer dump-autoload
```

### "Extension not loaded":
```bash
# Instale extensões necessárias
# Ubuntu/Debian:
sudo apt-get install php-mysql php-zip

# CentOS/RHEL:
sudo yum install php-mysqlnd php-zip
```

### Permissões:
```bash
chmod 755 cron/*.php
chmod 644 .env
chmod 755 backups logs
```

## 📚 Próximos Passos

Após a instalação:

1. 📖 Leia [BASIC_USAGE.md](BASIC_USAGE.md)
2. ⏰ Configure [CRON_SETUP.md](CRON_SETUP.md)
3. 🔔 Configure [WEBHOOK_SETUP.md](WEBHOOK_SETUP.md)

## 💡 Dicas

- Use `.env` para configurações sensíveis
- Nunca commite `.env` no Git
- Mantenha backups fora do document root
- Configure permissões adequadas

---

**Pronto!** Instalação concluída. 🎉