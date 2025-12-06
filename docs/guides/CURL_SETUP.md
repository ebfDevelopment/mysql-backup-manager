# 🌐 Guia de Configuração Cron via CURL

Este guia mostra como configurar backups automáticos usando CURL ao invés de CLI.

## 🎯 Quando usar CURL?

✅ **Use CURL quando:**
- Hospedagem compartilhada (sem acesso SSH)
- CPanel, Plesk ou similar
- Servidor web gerenciado
- Restrições de execução PHP CLI

✅ **Use CLI quando:**
- VPS ou servidor dedicado
- Acesso SSH/shell disponível
- Melhor performance é prioridade

## 🔐 Segurança

### **1. Configure o Token de Segurança**

Edite o `.env`:

```env
# Gere um token aleatório forte
CRON_TOKEN=8f9a2b5c1d3e4f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0
```

**Como gerar um token forte:**

```bash
# Linux/Mac
openssl rand -hex 32

# Ou use um gerador online
# https://www.uuidgenerator.net/
```

### **2. Proteja a pasta cron**

O arquivo `.htaccess` já está configurado para:
- ✅ Bloquear acesso a arquivos sensíveis (.txt, .log, .json)
- ✅ Permitir execução apenas via token
- ✅ (Opcional) Restringir por IP

**Para restringir por IP, edite `cron/.htaccess`:**

```apache
<Files "*.php">
    Order deny,allow
    Deny from all
    Allow from 127.0.0.1
    Allow from SEU.IP.AQUI
</Files>
```

## 📋 Configuração Passo a Passo

### **1. Copie os arquivos para o servidor:**

```
public_html/
├── cron/
│   ├── daily-backup.php
│   ├── weekly-backup.php
│   ├── multiple-databases-backup.php
│   ├── cleanup-old-backups.php
│   └── .htaccess
├── vendor/
├── .env
└── ...
```

### **2. Configure permissões:**

```bash
chmod 644 cron/*.php
chmod 644 cron/.htaccess
chmod 600 .env
```

### **3. Teste localmente via CURL:**

```bash
# Baixe o script de teste
chmod +x test-cron-curl.sh

# Edite e configure DOMAIN e TOKEN
nano test-cron-curl.sh

# Execute
./test-cron-curl.sh
```

### **4. Teste manual:**

```bash
# Sem token (deve retornar erro 403)
curl "https://seusite.com/cron/daily-backup.php"

# Com token (deve funcionar)
curl "https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN"

# Com saída formatada
curl "https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN" | jq
```

**Resposta esperada:**

```json
{
  "success": true,
  "timestamp": "2025-01-15 14:30:00",
  "mode": "HTTP",
  "file": "production_2025-01-15_14-30-00.zip",
  "size_mb": 12.45,
  "duration": 3.21,
  "path": "/var/www/backups/daily/production_2025-01-15_14-30-00.zip",
  "messages": [
    "=== Backup Diário Iniciado ===",
    "Google Drive configurado",
    "✓ Backup criado: production_2025-01-15_14-30-00.zip",
    "✓ Tamanho: 12.45 MB",
    "✓ Tempo: 3.21s",
    "=== Backup Concluído com Sucesso ==="
  ]
}
```

## ⏰ Configurar Crontab

### **Opção 1: CPanel**

1. Acesse **CPanel** → **Cron Jobs**
2. Adicione novo cron job:
   - **Minuto:** 0
   - **Hora:** 2
   - **Dia:** *
   - **Mês:** *
   - **Dia da Semana:** *
   - **Comando:**
     ```bash
     curl -s "https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN" >> /home/usuario/logs/backup.log 2>&1
     ```

### **Opção 2: Plesk**

1. Acesse **Plesk** → **Ferramentas & Configurações** → **Tarefas Agendadas**
2. Adicione nova tarefa:
   - **Comando:** 
     ```bash
     curl -s "https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN"
     ```
   - **Agendamento:** Todo dia às 2:00

### **Opção 3: SSH (crontab -e)**

```bash
# Edite o crontab
crontab -e

# Adicione as linhas
0 2 * * * curl -s "https://seusite.com/cron/daily-backup.php?token=SEU_TOKEN" >> /var/log/backup.log 2>&1
0 3 * * 0 curl -s "https://seusite.com/cron/weekly-backup.php?token=SEU_TOKEN" >> /var/log/backup-weekly.log 2>&1
```

## 🎯 Exemplos de Configuração

### **Backup Diário Simples:**

```bash
0 2 * * * curl -s "https://seusite.com/cron/daily-backup.php?token=TOKEN"
```

### **Com Timeout (30 segundos):**

```bash
0 2 * * * curl -s --max-time 30 "https://seusite.com/cron/daily-backup.php?token=TOKEN"
```

### **Com Retry (3 tentativas):**

```bash
0 2 * * * curl -s --retry 3 --retry-delay 5 "https://seusite.com/cron/daily-backup.php?token=TOKEN"
```

### **Salvando Log:**

```bash
0 2 * * * curl -s "https://seusite.com/cron/daily-backup.php?token=TOKEN" >> /var/log/backup.log 2>&1
```

### **Múltiplos Backups:**

```bash
# Múltiplos bancos - 1h
0 1 * * * curl -s "https://seusite.com/cron/multiple-databases-backup.php?token=TOKEN" >> /var/log/backup-multiple.log 2>&1

# Backup diário - 2h
0 2 * * * curl -s "https://seusite.com/cron/daily-backup.php?token=TOKEN" >> /var/log/backup-daily.log 2>&1

# Backup semanal - Domingo 3h
0 3 * * 0 curl -s "https://seusite.com/cron/weekly-backup.php?token=TOKEN" >> /var/log/backup-weekly.log 2>&1

# Limpeza - 4h
0 4 * * * curl -s "https://seusite.com/cron/cleanup-old-backups.php?token=TOKEN" >> /var/log/backup-cleanup.log 2>&1
```

## 📊 Monitoramento

### **Ver logs via CPanel:**

1. Acesse **File Manager**
2. Navegue até `/home/usuario/logs/`
3. Abra `backup.log`

### **Ver logs via SSH:**

```bash
# Log em tempo real
tail -f /var/log/backup.log

# Últimas 50 linhas
tail -n 50 /var/log/backup.log

# Procurar por erros
grep -i "error" /var/log/backup.log

# Procurar por sucessos
grep -i "success" /var/log/backup.log
```

### **Verificar última execução:**

```bash
# Via CURL
curl -s "https://seusite.com/cron/daily-backup.php?token=TOKEN" | jq '.timestamp'

# Via log
tail -n 1 /var/log/backup.log
```

## 🐛 Troubleshooting

### **Erro 403 - Forbidden**

```bash
# Causa: Token inválido ou ausente
# Solução:
1. Verifique se o token no .env está correto
2. Certifique-se que está passando o token na URL
3. Limpe cache do navegador/curl
```

### **Timeout / Script não responde**

```bash
# Causa: Backup muito grande ou servidor lento
# Solução: Aumente o timeout

# No crontab:
curl -s --max-time 300 "https://..."  # 5 minutos

# No PHP (no script):
set_time_limit(300);  # 5 minutos
```

### **"Token inválido" mesmo com token correto**

```bash
# Causa: .env não está sendo carregado
# Solução: Verifique o caminho no script

# No script, altere:
if (file_exists(__DIR__ . '/../.env')) {
    // para o caminho correto do seu .env
}
```

### **Servidor retorna HTML ao invés de JSON**

```bash
# Causa: Erro PHP sendo exibido
# Solução:
1. Verifique logs do PHP
2. Desative display_errors em produção
3. Use error_log para debug
```

### **Funciona manual mas não no cron**

```bash
# Causa: Caminho relativo ou permissões
# Solução:
1. Use caminhos absolutos no script
2. Verifique permissões dos arquivos
3. Teste com o mesmo usuário do cron
```

## 🔒 Segurança Avançada

### **1. IP Whitelist (Recomendado)**

No `.htaccess`:

```apache
<Files "*.php">
    Order deny,allow
    Deny from all
    Allow from 127.0.0.1        # Localhost
    Allow from IP.DO.SERVIDOR    # IP do seu servidor
</Files>
```

### **2. HTTPS Obrigatório**

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^cron/ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### **3. Rate Limiting**

```apache
# Limite 1 requisição por minuto por IP
<IfModule mod_ratelimit.c>
    SetOutputFilter RATE_LIMIT
    SetEnv rate-limit 100
</IfModule>
```

### **4. Token Rotation**

```bash
# Troque o token periodicamente
# 1. Gere novo token
openssl rand -hex 32

# 2. Atualize .env
CRON_TOKEN=novo_token_aqui

# 3. Atualize crontab
crontab -e
```

## 📈 Boas Práticas

1. ✅ **Use HTTPS sempre**
2. ✅ **Token forte (32+ caracteres)**
3. ✅ **Logs fora do document root**
4. ✅ **Monitore execuções**
5. ✅ **Teste antes de agendar**
6. ✅ **Backup do .env**
7. ✅ **Notificações por email**
8. ✅ **Rotação de tokens**

## 🎉 Pronto!

Agora você tem backups automáticos via CURL! 🚀

Para dúvidas, consulte o `CRON_GUIDE.md` ou abra uma issue.