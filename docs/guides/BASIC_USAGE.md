# 📗 Guia de Uso Básico

Aprenda a usar a biblioteca MySQL Backup Manager.

## 🎯 Conceitos Básicos

A biblioteca tem 3 componentes principais:

1. **BackupConfig** - Configurações do banco
2. **BackupManager** - Gerencia backups
3. **Storage** (opcional) - Onde guardar (Drive, S3, etc)

## 💡 Exemplo Mínimo

```php
<?php
require 'vendor/autoload.php';

use MysqlBackup\BackupManager;
use MysqlBackup\Config\BackupConfig;

// 1. Configure
$config = new BackupConfig([
    'host' => 'localhost',
    'database' => 'meu_banco',
    'username' => 'root',
    'password' => 'senha',
    'backup_path' => __DIR__ . '/backups'
]);

// 2. Crie o gerenciador
$manager = new BackupManager($config);

// 3. Faça o backup
$arquivo = $manager->backupToZip();

echo "Backup criado: {$arquivo}";
```

## 📦 Formatos de Backup

### SQL Puro:

```php
$arquivo = $manager->backupToSql();
// Resultado: /backups/database_2025-01-15_14-30-00.sql
```

**Vantagens:**
- ✅ Legível
- ✅ Editável
- ✅ Importável diretamente

### ZIP Compactado:

```php
$arquivo = $manager->backupToZip();
// Resultado: /backups/database_2025-01-15_14-30-00.zip
```

**Vantagens:**
- ✅ Menor tamanho
- ✅ Mais rápido de transferir
- ✅ Inclui o SQL dentro

## 🎨 Personalização

### Nome Customizado:

```php
// Com timestamp automático (padrão)
$manager->backupToZip();
// database_2025-01-15_14-30-00.zip

// Nome personalizado
$manager->backupToZip('meu_backup_especial.zip');
// meu_backup_especial.zip
```

### Caminho Dinâmico:

```php
$config = new BackupConfig([
    'host' => 'localhost',
    'database' => 'meu_banco',
    'username' => 'root',
    'password' => 'senha',
    'backup_path' => __DIR__ . '/backups/' . date('Y-m-d')
]);

$manager = new BackupManager($config);
$manager->backupToZip();
// Salva em: /backups/2025-01-15/database_*.zip
```

## ☁️ Google Drive

### 1. Configuração Inicial:

```php
use MysqlBackup\Storage\GoogleDriveStorage;

$storage = new GoogleDriveStorage(
    __DIR__ . '/credentials.json'  // Arquivo de credenciais
);

$manager->setStorage($storage);
```

### 2. Backup Local + Drive:

```php
$arquivo = $manager->backupToZip();
// Salva localmente E envia para o Google Drive
```

### 3. Pasta Específica no Drive:

```php
$storage = new GoogleDriveStorage(
    __DIR__ . '/credentials.json',
    'ID_DA_PASTA_NO_DRIVE'
);
```

### 4. Operações no Drive:

```php
// Listar arquivos
$files = $storage->listFiles(10);
foreach ($files as $file) {
    echo $file->getName() . " - ";
    echo round($file->getSize() / 1024 / 1024, 2) . " MB\n";
}

// Deletar arquivo
$storage->deleteFile('ID_DO_ARQUIVO');
```

## 🔄 Múltiplos Bancos

```php
$databases = ['banco1', 'banco2', 'banco3'];

foreach ($databases as $db) {
    $config = new BackupConfig([
        'host' => 'localhost',
        'database' => $db,
        'username' => 'root',
        'password' => 'senha',
        'backup_path' => __DIR__ . "/backups/{$db}"
    ]);
    
    $manager = new BackupManager($config);
    $arquivo = $manager->backupToZip();
    
    echo "✅ {$db}: " . basename($arquivo) . "\n";
}
```

## ⚙️ Opções de Configuração

### Todas as opções disponíveis:

```php
$config = new BackupConfig([
    // Obrigatórios
    'host' => 'localhost',
    'database' => 'nome_banco',
    'username' => 'usuario',
    'password' => 'senha',
    
    // Opcionais
    'port' => 3306,                      // Padrão: 3306
    'charset' => 'utf8mb4',              // Padrão: utf8mb4
    'backup_path' => '/path/backups'     // Padrão: sys_get_temp_dir()
]);
```

### Métodos disponíveis:

```php
// Getters
$config->getHost();
$config->getDatabase();
$config->getUsername();
$config->getPassword();
$config->getPort();
$config->getCharset();
$config->getBackupPath();
$config->getDsn();  // String de conexão PDO
```

## 🛠️ Exemplos Práticos

### Backup Condicional:

```php
// Só faz backup se o banco existir
try {
    $manager = new BackupManager($config);
    $arquivo = $manager->backupToZip();
    echo "✅ Backup: {$arquivo}\n";
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}
```

### Backup com Notificação:

```php
try {
    $arquivo = $manager->backupToZip();
    $size = round(filesize($arquivo) / 1024 / 1024, 2);
    
    mail(
        'admin@site.com',
        'Backup Concluído',
        "Backup realizado: " . basename($arquivo) . " ({$size} MB)"
    );
} catch (Exception $e) {
    mail(
        'admin@site.com',
        'Backup FALHOU',
        "Erro: {$e->getMessage()}"
    );
}
```

### Backup com Validação:

```php
$arquivo = $manager->backupToZip();

// Verifica se foi criado
if (file_exists($arquivo)) {
    $size = filesize($arquivo);
    
    // Valida tamanho mínimo (1 MB)
    if ($size < 1024 * 1024) {
        echo "⚠️  Backup muito pequeno, pode estar incompleto!\n";
    } else {
        echo "✅ Backup válido: " . round($size / 1024 / 1024, 2) . " MB\n";
    }
}
```

## 🔒 Segurança

### Use variáveis de ambiente:

```php
$config = new BackupConfig([
    'host' => getenv('DB_HOST'),
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'backup_path' => getenv('BACKUP_PATH')
]);
```

### Permissões recomendadas:

```bash
# Scripts
chmod 755 backup.php

# Backups (apenas dono)
chmod 700 backups/

# Arquivo .env
chmod 600 .env
```

## ❌ Tratamento de Erros

```php
try {
    $manager = new BackupManager($config);
    $arquivo = $manager->backupToZip();
    
} catch (RuntimeException $e) {
    // Erro de conexão, permissão, etc
    error_log("Backup falhou: " . $e->getMessage());
    
} catch (Exception $e) {
    // Outros erros
    error_log("Erro inesperado: " . $e->getMessage());
}
```

## 📚 Próximos Passos

- ⏰ [Automatizar com Cron](CRON_SETUP.md)
- 🌐 [Usar via CURL](CURL_SETUP.md)
- 🔔 [Configurar Webhooks](WEBHOOK_SETUP.md)
- 🧪 [Testes](TESTING.md)

---

**Dúvidas?** Veja [TROUBLESHOOTING.md](TROUBLESHOOTING.md)