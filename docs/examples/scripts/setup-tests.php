<?php
/**
 * Script para preparar ambiente de testes
 * Execute antes de rodar os testes pela primeira vez
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "========================================\n";
echo "  Configuração de Ambiente de Testes\n";
echo "========================================\n\n";

// 1. Verifica MySQL
echo "1. Verificando MySQL...\n";
if (Tests\TestHelper::isMySQLAvailable()) {
    echo "   ✅ MySQL disponível\n\n";
} else {
    die("   ❌ MySQL não está disponível!\n" .
        "   Inicie o MySQL e tente novamente.\n\n");
}

// 2. Cria banco de teste
echo "2. Criando banco de dados de teste...\n";
Tests\TestHelper::createTestDatabase();
echo "\n";

// 3. Cria pasta de testes temporários
echo "3. Criando pasta temporária...\n";
$tempPath = __DIR__ . '/tests/temp';
if (!is_dir($tempPath)) {
    mkdir($tempPath, 0755, true);
    echo "   ✅ Pasta criada: {$tempPath}\n\n";
} else {
    echo "   ✅ Pasta já existe: {$tempPath}\n\n";
}

// 4. Verifica PHPUnit
echo "4. Verificando PHPUnit...\n";
if (class_exists('PHPUnit\Framework\TestCase')) {
    echo "   ✅ PHPUnit instalado\n\n";
} else {
    echo "   ⚠️  PHPUnit não encontrado\n";
    echo "   Execute: composer require --dev phpunit/phpunit:^9.5\n\n";
}

// 5. Verifica biblioteca
echo "5. Verificando mysql-backup...\n";
if (class_exists('MysqlBackup\BackupManager')) {
    echo "   ✅ Biblioteca instalada\n\n";
} else {
    echo "   ❌ Biblioteca não encontrada\n";
    echo "   Execute: composer require seu-usuario/mysql-backup\n\n";
}

// 6. Verifica Google API (opcional)
echo "6. Verificando Google API Client (opcional)...\n";
if (class_exists('Google_Client')) {
    echo "   ✅ Google API Client instalado\n";
    
    // Verifica credentials
    if (file_exists(__DIR__ . '/credentials.json')) {
        echo "   ✅ credentials.json encontrado\n";
    } else {
        echo "   ⚠️  credentials.json não encontrado\n";
        echo "   Testes do Google Drive serão pulados\n";
    }
    
    // Verifica token
    if (file_exists(__DIR__ . '/token.json')) {
        echo "   ✅ token.json encontrado\n";
    } else {
        echo "   ⚠️  token.json não encontrado\n";
        echo "   Execute: php authenticate-oob.php\n";
    }
} else {
    echo "   ⚠️  Google API Client não instalado\n";
    echo "   Testes do Google Drive serão pulados\n";
    echo "   Para instalar: composer require google/apiclient:^2.15\n";
}

echo "\n";
echo "========================================\n";
echo "✅ Configuração concluída!\n";
echo "========================================\n\n";

echo "📝 Próximos passos:\n";
echo "1. Execute os testes: composer test\n";
echo "   Ou: vendor/bin/phpunit\n\n";
echo "2. Para coverage: composer test-coverage\n";
echo "   Ou: vendor/bin/phpunit --coverage-html coverage\n\n";
echo "3. Para limpar depois: php cleanup-tests.php\n\n";