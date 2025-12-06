# ⏰ Guia de Configuração de Backups Automáticos com Cron

Este guia mostra como configurar backups automáticos usando cron jobs no Linux.

## 📁 Estrutura de Arquivos

```
sua-aplicacao/
├── cron/
│   ├── daily-backup.php                # Backup diário
│   ├── weekly-backup.php               # Backup semanal com rotação
│   ├── multiple-databases-backup.php   # Múltiplos bancos
│   └── cleanup-old-backups.php         # Limpeza automática
├── .env                                 # Configurações (não commitar!)
├── .env.example                         # Exemplo de configurações
├── install-cron.sh                      # Instalador automático
└── crontab-examples.txt                 # Exemplos de agendamento
```

## 🚀 Instalação Rápida

### **Opção 1: Instalador Automático**

```bash
# 1. Dê permissão de execução
chmod +x install-cron.sh

# 2. Execute o instalador
./install-cron.sh

# 3. Escolha as tarefas desejadas
```

### **Opção 2: Manual**

```bash
# 1. Edite o crontab
crontab -e

# 2. Adicione as linhas desejadas (veja crontab-examples.txt)

# 3. Salve e saia (Ctrl+O, Enter, Ctrl+X no nano)
```

## ⚙️ Configuração

### **1. Configure o arquivo .env:**

```bash
# Copie o exemplo
cp .env.example .env

# Edite com suas configurações
nano .env
```

**Exemplo de .env:**
```env
DB_HOST=localhost
DB_NAME=production
DB_USER=backup_user
DB_PASS=senha_segura

BACKUP_PATH=/var/backups/mysql
KEEP_BACKUPS_DAYS=7

# Google Drive (opcional)
GDRIVE_FOLDER_ID=1a2B3c4D5e6F
CLEANUP_GDRIVE=true

# Notificações
NOTIFY_EMAIL=admin@example.com
```

### **2. Crie o usuário de backup MySQL (recomendado):**

```sql
-- Crie um usuário apenas para backup (mais seguro)
CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'senha_segura';

-- Dê permissões apenas de leitura
GRANT SELECT, LOCK TABLES, SHOW VIEW ON *.* TO 'backup_user'@'localhost';

FLUSH PRIVILEGES;
```

### **3. Configure permissões:**

```bash
# Permissões dos scripts
chmod +x cron/*.php

# Crie pasta de backups
sudo mkdir -p /var/backups/mysql
sudo chown $USER:$USER /var/backups/mysql
sudo chmod 755 /var/backups/mysql

# Crie pasta de logs
sudo mkdir -p /var/log/mysql-backup
sudo chmod 755 /var/log/mysql-backup
```

## 📋 Scripts Disponíveis

### **1. daily-backup.php**

**O que faz:**
- Backup diário completo
- Envia para Google Drive (opcional)
- Envia notificação por email

**Agendamento sugerido:**
```bash
0 2 * * * /usr/bin/php /caminho/cron/daily-backup.php >> /var/log/mysql-backup/daily.log 2>&1
```

### **2. weekly-backup.php**

**O que faz:**
- Backup semanal
- Mantém apenas as últimas 4 semanas
- Rotação automática

**Agendamento sugerido:**
```bash
0 3 * * 0 /usr/bin/php /caminho/cron/weekly-backup.php >> /var/log/mysql-backup/weekly.log 2>&1
```

### **3. multiple-databases-backup.php**

**O que faz:**
- Backup de vários bancos em sequência
- Relatório consolidado
- Notificação em caso de falha

**Agendamento sugerido:**
```bash
0 1 * * * /usr/bin/php /caminho/cron/multiple-databases-backup.php >> /var/log/mysql-backup/multiple.log 2>&1
```

**Configuração:**
Edite o array `$databases` no arquivo:
```php
$databases = [
    'production_db',
    'analytics_db',
    'logs_db',
];
```

### **4. cleanup-old-backups.php**

**O que faz:**
- Remove backups locais antigos
- Remove backups do Google Drive (opcional)
- Libera espaço em disco

**Agendamento sugerido:**
```bash
0 4 * * * /usr/bin/php /caminho/cron/cleanup-old-backups.php >> /var/log/mysql-backup/cleanup.log 2>&1
```

## 🎯 Exemplos de Agendamento

### **Backup a cada 6 horas:**
```bash
0 */6 * * * /usr/bin/php /caminho/cron/daily-backup.php
```

### **Backup no horário comercial (8h-18h):**
```bash
0 8-18 * * 1-5 /usr/bin/php /caminho/cron/daily-backup.php
```

### **Backup mensal (dia 1 às 1h):**
```bash
0 1 1 * * /usr/bin/php /caminho/cron/weekly-backup.php
```

### **Configuração completa recomendada:**
```bash
# Múltiplos bancos - 1h
0 1 * * * /usr/bin/php /var/www/html/cron/multiple-databases-backup.php >> /var/log/mysql-backup/multiple.log 2>&1

# Backup diário - 2h
0 2 * * * /usr/bin/php /var/www/html/cron/daily-backup.php >> /var/log/mysql-backup/daily.log 2>&1

# Backup semanal - Domingo 3h
0 3 * * 0 /usr/bin/php /var/www/html/cron/weekly-backup.php >> /var/log/mysql-backup/weekly.log 2>&1

# Limpeza - 4h
0 4 * * * /usr/bin/php /var/www/html/cron/cleanup-old-backups.php >> /var/log/mysql-backup/cleanup.log 2>&1
```

## 📊 Monitoramento

### **Ver logs em tempo real:**
```bash
tail -f /var/log/mysql-backup/daily.log
tail -f /var/log/mysql-backup/weekly.log
tail -f /var/log/mysql-backup/cleanup.log
```

### **Ver últimas linhas:**
```bash
tail -n 50 /var/log/mysql-backup/daily.log
```

### **Verificar tarefas agendadas:**
```bash
crontab -l
```

### **Ver execuções do cron:**
```bash
grep CRON /var/log/syslog
```

### **Testar script manualmente:**
```bash
/usr/bin/php /var/www/html/cron/daily-backup.php
```

## 🔔 Notificações por Email

Para receber notificações, configure no `.env`:

```env
NOTIFY_EMAIL=seu-email@example.com
```

**Nota:** Requer a função `mail()` configurada no PHP.

### **Configurar Postfix (Ubuntu/Debian):**
```bash
sudo apt-get install postfix mailutils

# Configure para "Internet Site"
# Hostname: seu-dominio.com
```

## 🐛 Troubleshooting

### **Cron não executa:**
```bash
# Verifique se o serviço está rodando
sudo systemctl status cron

# Inicie se necessário
sudo systemctl start cron

# Ver logs do cron
sudo tail -f /var/log/syslog | grep CRON
```

### **Permissão negada:**
```bash
# Dê permissão de execução
chmod +x cron/*.php

# Verifique o dono
ls -la cron/

# Ajuste se necessário
sudo chown -R $USER:$USER cron/
```

### **PHP não encontrado:**
```bash
# Descubra o caminho do PHP
which php

# Use o caminho completo no crontab
/usr/bin/php /caminho/script.php
```

### **Script funciona manual mas não no cron:**
```bash
# Problema comum: PATH diferente

# Solução 1: Use caminhos absolutos no script
/usr/bin/php /var/www/html/cron/daily-backup.php

# Solução 2: Defina PATH no crontab
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
0 2 * * * php /var/www/html/cron/daily-backup.php
```

### **Backup muito grande:**
```bash
# Considere compressão adicional
# Ou use tabelas específicas
# Ou implemente backup incremental
```

## 📈 Boas Práticas

### **1. Teste antes de agendar:**
```bash
# Execute manualmente primeiro
php cron/daily-backup.php

# Verifique se criou o backup
ls -lh backups/
```

### **2. Monitore o espaço em disco:**
```bash
# Verifique espaço disponível
df -h

# Espaço usado pelos backups
du -sh /var/backups/mysql
```

### **3. Rotação de logs:**
```bash
# Crie /etc/logrotate.d/mysql-backup
sudo nano /etc/logrotate.d/mysql-backup

# Conteúdo:
/var/log/mysql-backup/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
}
```

### **4. Teste restauração:**
```bash
# Periodicamente, teste se os backups funcionam:
unzip backup.zip
mysql -u root -p database_test < backup.sql
```

### **5. Segurança:**
```bash
# Proteja o .env
chmod 600 .env

# Backups não devem ser acessíveis via web
# Coloque fora do document root
```

## 🎉 Pronto!

Agora você tem backups automáticos configurados! 🚀

Para dúvidas ou problemas, consulte os logs ou abra uma issue no repositório.