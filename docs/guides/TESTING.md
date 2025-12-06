# 🧪 Guia de Testes

Testes unitários e de integração para validar o funcionamento da biblioteca MySQL Backup.

## 📋 Pré-requisitos

- PHP >= 7.4
- MySQL rodando localmente
- Composer instalado
- Biblioteca mysql-backup instalada

## 🚀 Instalação

### 1. Instale as dependências:

```bash
composer install
```

### 2. Configure o ambiente:

```bash
php setup-tests.php
```

Este script irá:
- ✅ Verificar MySQL
- ✅ Criar banco de dados de teste (`test_backup`)
- ✅ Criar tabelas e dados de exemplo
- ✅ Criar pasta temporária para testes
- ✅ Verificar dependências

## 🧪 Executando os Testes

### Todos os testes:

```bash
composer test
```

Ou:

```bash
vendor/bin/phpunit
```

### Testes específicos:

```bash
# Apenas testes de backup
vendor/bin/phpunit tests/BackupTest.php

# Apenas testes do Google Drive
vendor/bin/phpunit tests/GoogleDriveTest.php

# Apenas testes de integração
vendor/bin/phpunit tests/IntegrationTest.php
```

### Teste específico:

```bash
vendor/bin/phpunit --filter testBackupToSql
```

### Com coverage (HTML):

```bash
composer test-coverage
```

Depois abra: `coverage/index.html` no navegador

## 📊 Estrutura dos Testes

```
tests/
├── BackupTest.php           # Testes básicos da biblioteca
├── GoogleDriveTest.php      # Testes de integração com Drive
├── IntegrationTest.php      # Testes de cenários completos
├── TestHelper.php           # Funções auxiliares
└── temp/                    # Arquivos temporários (auto-gerado)
```

## 🎯 Casos de Teste

### BackupTest.php

- ✅ Criação de configuração
- ✅ Criação do BackupManager
- ✅ Backup para SQL
- ✅ Backup para ZIP
- ✅ Geração de nomes de arquivo
- ✅ Nomes personalizados
- ✅ Criação automática de pastas
- ✅ Tratamento de erros

### GoogleDriveTest.php

- ✅ Criação do GoogleDriveStorage
- ✅ Configuração com pasta específica
- ✅ Validação de credenciais
- ✅ Upload para Google Drive
- ✅ Listagem de arquivos
- ✅ Verificação de dependências

### IntegrationTest.php

- ✅ Cenário de backup diário
- ✅ Múltiplos bancos de dados
- ✅ Limpeza de arquivos antigos
- ✅ Verificação de integridade
- ✅ Teste de performance

## ⚙️ Configuração

### Variáveis de Ambiente

Configure no `phpunit.xml`:

```xml
<php>
    <env name="DB_HOST" value="localhost"/>
    <env name="DB_NAME" value="test_backup"/>
    <env name="DB_USER" value="root"/>
    <env name="DB_PASS" value=""/>
    <env name="BACKUP_PATH" value="./tests/temp"/>
</php>
```

### Google Drive (Opcional)

Para testar integração com Google Drive:

1. **Configure credentials.json:**
   ```bash
   # Coloque na raiz do projeto
   cp path/to/credentials.json ./
   ```

2. **Autentique:**
   ```bash
   php authenticate-oob.php
   ```

3. **Execute os testes:**
   ```bash
   vendor/bin/phpunit tests/GoogleDriveTest.php
   ```

**Nota:** Se não configurar, os testes do Google Drive serão pulados automaticamente.

## 🐛 Troubleshooting

### "MySQL não disponível"

```bash
# Verifique se o MySQL está rodando
mysql -u root -p

# Ou inicie o XAMPP/WAMP
```

### "Banco de dados não encontrado"

```bash
# Execute novamente o setup
php setup-tests.php
```

### "Pasta temporária não existe"

```bash
# Crie manualmente
mkdir -p tests/temp
chmod 755 tests/temp
```

### "PHPUnit não encontrado"

```bash
composer require --dev phpunit/phpunit:^9.5
```

### Testes do Google Drive falham

- Verifique se `credentials.json` existe
- Execute `php authenticate-oob.php`
- Verifique se `token.json` foi criado
- Confira as configurações do antivírus/SSL

## 🧹 Limpeza

Após os testes, limpe o ambiente:

```bash
php cleanup-tests.php
```

Isso irá:
- 🗑️ Remover banco de dados de teste
- 🗑️ Remover arquivos temporários
- 🗑️ Remover relatório de coverage

## 📈 Cobertura de Código

### Gerar relatório:

```bash
vendor/bin/phpunit --coverage-html coverage
```

### Visualizar:

Abra `coverage/index.html` no navegador.

### Meta de cobertura:

- **Mínimo:** 70%
- **Ideal:** 80%+

## 🎯 Boas Práticas

### Ao adicionar novos testes:

1. **Nomeie claramente:**
   ```php
   public function testBackupWithCustomFilename(): void
   ```

2. **Use assertions apropriadas:**
   ```php
   $this->assertFileExists($file);
   $this->assertStringEndsWith('.sql', $file);
   ```

3. **Limpe depois:**
   ```php
   protected function tearDown(): void {
       // Limpa recursos
   }
   ```

4. **Documente:**
   ```php
   /**
    * Testa se o backup ZIP contém SQL válido
    */
   public function testZipContainsValidSql(): void
   ```

### Testes devem ser:

- ✅ **Independentes** - Cada teste roda sozinho
- ✅ **Rápidos** - Execute em < 5 segundos
- ✅ **Determinísticos** - Sempre mesmo resultado
- ✅ **Isolados** - Não afeta outros testes
- ✅ **Legíveis** - Fácil entender o que testa

## 📚 Recursos

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
- [Assertions](https://phpunit.de/manual/current/en/assertions.html)

## 🤝 Contribuindo

Ao adicionar novas funcionalidades:

1. Escreva os testes ANTES
2. Implemente a funcionalidade
3. Garanta que todos os testes passem
4. Mantenha cobertura > 70%

---

**Dúvidas?** Abra uma issue no repositório! 🚀