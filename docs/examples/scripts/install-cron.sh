#!/bin/bash
# Script para instalar tarefas de cron automaticamente

echo "========================================="
echo "  Instalador de Tarefas Cron - Backup"
echo "========================================="
echo ""

# Detecta o caminho do projeto
PROJECT_PATH=$(cd "$(dirname "$0")/.." && pwd)
PHP_PATH=$(which php)

echo "Caminho do projeto: $PROJECT_PATH"
echo "PHP: $PHP_PATH"
echo ""

# Verifica se PHP existe
if [ ! -f "$PHP_PATH" ]; then
    echo "❌ PHP não encontrado!"
    echo "   Instale o PHP primeiro."
    exit 1
fi

# Verifica se os scripts existem
if [ ! -f "$PROJECT_PATH/cron/daily-backup.php" ]; then
    echo "❌ Scripts de cron não encontrados!"
    echo "   Certifique-se de que os arquivos estão em $PROJECT_PATH/cron/"
    exit 1
fi

echo "✅ Scripts encontrados"
echo ""

# Cria diretório de logs se não existir
LOG_DIR="/var/log/mysql-backup"
if [ ! -d "$LOG_DIR" ]; then
    echo "Criando diretório de logs..."
    sudo mkdir -p "$LOG_DIR"
    sudo chmod 755 "$LOG_DIR"
    echo "✅ Diretório criado: $LOG_DIR"
fi

echo ""
echo "========================================="
echo "Escolha as tarefas para instalar:"
echo "========================================="
echo "1. Backup Diário (2h)"
echo "2. Backup Semanal (Domingo 3h)"
echo "3. Múltiplos Bancos (1h)"
echo "4. Limpeza Automática (4h)"
echo "5. Todas as acima"
echo "0. Cancelar"
echo ""
read -p "Opção: " option

# Monta as linhas do crontab
CRON_LINES=""

case $option in
    1)
        CRON_LINES="0 2 * * * $PHP_PATH $PROJECT_PATH/cron/daily-backup.php >> $LOG_DIR/daily.log 2>&1"
        ;;
    2)
        CRON_LINES="0 3 * * 0 $PHP_PATH $PROJECT_PATH/cron/weekly-backup.php >> $LOG_DIR/weekly.log 2>&1"
        ;;
    3)
        CRON_LINES="0 1 * * * $PHP_PATH $PROJECT_PATH/cron/multiple-databases-backup.php >> $LOG_DIR/multiple.log 2>&1"
        ;;
    4)
        CRON_LINES="0 4 * * * $PHP_PATH $PROJECT_PATH/cron/cleanup-old-backups.php >> $LOG_DIR/cleanup.log 2>&1"
        ;;
    5)
        CRON_LINES="# Backup MySQL - Instalado em $(date)
0 1 * * * $PHP_PATH $PROJECT_PATH/cron/multiple-databases-backup.php >> $LOG_DIR/multiple.log 2>&1
0 2 * * * $PHP_PATH $PROJECT_PATH/cron/daily-backup.php >> $LOG_DIR/daily.log 2>&1
0 3 * * 0 $PHP_PATH $PROJECT_PATH/cron/weekly-backup.php >> $LOG_DIR/weekly.log 2>&1
0 4 * * * $PHP_PATH $PROJECT_PATH/cron/cleanup-old-backups.php >> $LOG_DIR/cleanup.log 2>&1"
        ;;
    0)
        echo "Cancelado."
        exit 0
        ;;
    *)
        echo "❌ Opção inválida!"
        exit 1
        ;;
esac

echo ""
echo "========================================="
echo "Prévia das tarefas:"
echo "========================================="
echo "$CRON_LINES"
echo ""
read -p "Confirma instalação? (s/n): " confirm

if [ "$confirm" != "s" ] && [ "$confirm" != "S" ]; then
    echo "Cancelado."
    exit 0
fi

# Adiciona ao crontab
(crontab -l 2>/dev/null; echo "$CRON_LINES") | crontab -

echo ""
echo "✅ Tarefas instaladas com sucesso!"
echo ""
echo "========================================="
echo "Comandos úteis:"
echo "========================================="
echo "Ver tarefas agendadas:"
echo "  crontab -l"
echo ""
echo "Ver logs:"
echo "  tail -f $LOG_DIR/daily.log"
echo ""
echo "Testar manualmente:"
echo "  $PHP_PATH $PROJECT_PATH/cron/daily-backup.php"
echo ""
echo "Remover tarefas:"
echo "  crontab -e"
echo "========================================="
echo ""