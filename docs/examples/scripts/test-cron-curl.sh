#!/bin/bash
# Script para testar cron via CURL

echo "========================================="
echo "  Teste de Cron via CURL"
echo "========================================="
echo ""

# Configurações
DOMAIN="https://seusite.com"  # ← ALTERE AQUI
TOKEN="SEU_TOKEN_SEGURO"       # ← ALTERE AQUI

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verifica se as variáveis foram alteradas
if [ "$DOMAIN" == "https://seusite.com" ] || [ "$TOKEN" == "SEU_TOKEN_SEGURO" ]; then
    echo -e "${RED}❌ ERRO: Configure DOMAIN e TOKEN neste script!${NC}"
    echo ""
    echo "Edite este arquivo e altere:"
    echo "  DOMAIN=\"https://seu-dominio.com\""
    echo "  TOKEN=\"seu_token_do_arquivo_.env\""
    exit 1
fi

# Testa conectividade
echo "1. Testando conectividade..."
if curl -s --head --max-time 5 "$DOMAIN" | grep "200 OK" > /dev/null; then
    echo -e "   ${GREEN}✓${NC} Servidor acessível"
else
    echo -e "   ${RED}✗${NC} Servidor não responde"
    exit 1
fi

echo ""

# Testa sem token (deve falhar)
echo "2. Testando sem token (deve falhar)..."
RESPONSE=$(curl -s -w "\n%{http_code}" "$DOMAIN/cron/daily-backup.php")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "403" ]; then
    echo -e "   ${GREEN}✓${NC} Segurança OK - Negou acesso sem token"
else
    echo -e "   ${YELLOW}⚠${NC}  Retornou código: $HTTP_CODE"
    echo "   Resposta: $BODY"
fi

echo ""

# Testa com token inválido (deve falhar)
echo "3. Testando com token inválido (deve falhar)..."
RESPONSE=$(curl -s -w "\n%{http_code}" "$DOMAIN/cron/daily-backup.php?token=token_errado")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" == "403" ]; then
    echo -e "   ${GREEN}✓${NC} Segurança OK - Negou token inválido"
else
    echo -e "   ${YELLOW}⚠${NC}  Retornou código: $HTTP_CODE"
fi

echo ""

# Testa com token correto
echo "4. Testando com token correto..."
echo "   URL: $DOMAIN/cron/daily-backup.php?token=$TOKEN"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" "$DOMAIN/cron/daily-backup.php?token=$TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "   HTTP Code: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "   ${GREEN}✓${NC} Requisição bem sucedida!"
    echo ""
    echo "   Resposta:"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    echo ""
    
    # Verifica se teve sucesso
    if echo "$BODY" | grep -q '"success":true' || echo "$BODY" | grep -q '"success": true'; then
        echo -e "   ${GREEN}✓ BACKUP EXECUTADO COM SUCESSO!${NC}"
    else
        echo -e "   ${RED}✗ Backup falhou. Veja detalhes acima.${NC}"
    fi
else
    echo -e "   ${RED}✗${NC} Falha na requisição"
    echo ""
    echo "   Resposta:"
    echo "$BODY"
fi

echo ""
echo "========================================="
echo "  Teste concluído"
echo "========================================="
echo ""

# Dicas
echo "💡 Próximos passos:"
echo ""
echo "Se o teste funcionou:"
echo "  1. Adicione ao crontab:"
echo "     crontab -e"
echo ""
echo "  2. Adicione a linha:"
echo "     0 2 * * * curl -s \"$DOMAIN/cron/daily-backup.php?token=$TOKEN\" >> /var/log/backup.log 2>&1"
echo ""
echo "  3. Monitore os logs:"
echo "     tail -f /var/log/backup.log"
echo ""