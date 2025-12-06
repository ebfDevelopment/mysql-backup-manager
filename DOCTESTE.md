# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).


## [1.2.0] - 2025-01-15

### ✨ Adicionado
- Integração completa com Google Drive
  - Upload automático de backups
  - Listagem de arquivos
  - Remoção de arquivos
  - Suporte a pastas específicas
- **HttpPostStorage** - Envio de backups via HTTP POST
  - Suporte a autenticação (Bearer, API Key, Token)
  - Headers personalizados
  - Metadados customizáveis
  - Timeout configurável
  - Endpoint receptor incluído nos exemplos
- **WebhookNotifier** - Sistema de notificações em tempo real
  - Notificações de sucesso, falha e início de backup
  - Suporte a múltiplos webhooks
  - Autenticação via headers
  - Eventos customizáveis
  - Exemplos de integração (Slack, Discord, Telegram, Teams)
  - Endpoint receptor com handlers de eventos
- Documentação completa
  - Guia de instalação
  - Guia de uso básico
  - Configuração de Cron (CLI e CURL)
  - Setup de HTTP POST
  - Setup de Webhooks (Notificações)
  - Setup de Webhooks (Logs internos)
  - Troubleshooting

## [1.1.0] - 2025-01-15

### ✨ Adicionado
- Integração completa com Google Drive
  - Upload automático de backups
  - Listagem de arquivos
  - Remoção de arquivos
  - Suporte a pastas específicas
- **HttpPostStorage** - Envio de backups via HTTP POST
  - Suporte a autenticação (Bearer, API Key, Token)
  - Headers personalizados
  - Metadados customizáveis
  - Timeout configurável
  - Endpoint receptor incluído nos exemplos
- **WebhookNotifier** - Sistema de notificações em tempo real
  - Notificações de sucesso, falha e início de backup
  - Suporte a múltiplos webhooks
  - Autenticação via headers
  - Eventos customizáveis
  - Exemplos de integração (Slack, Discord, Telegram, Teams)
  - Endpoint receptor com handlers de eventos
- Sistema de Webhooks interno (logs locais)
  - Registro automático de eventos
  - Múltiplos formatos (JSON, TXT, CSV)
  - Visualizador web de logs
  - API para leitura programática
- Exemplos de scripts Cron
  - Backup diário com notificações
  - Backup semanal com rotação automática
  - Backup de múltiplos bancos
  - Limpeza automática de backups antigos
- Suporte a Cron via CURL
  - Autenticação por token
  - Ideal para hospedagem compartilhada
  - Resposta JSON estruturada
- Classe base `CronBase` reutilizável
- Scripts utilitários
  - Instalador de cron jobs
  - Testes automatizados
  - Script de setup
- Documentação completa
  - Guia de instalação
  - Guia de uso básico
  - Configuração de Cron (CLI e CURL)
  - Troubleshooting

### 🔧 Modificado
- Google API Client agora é dependência opcional (`suggest`)
- Melhorias na documentação
- Estrutura reorganizada com exemplos em `docs/`

### 🐛 Corrigido
- Compatibilidade SSL no Windows/XAMPP
- Tratamento de erros melhorado
- Validação de permissões de arquivo

## [1.0.0] - 2025-01-10

### ✨ Adicionado
- Release inicial
- Backup para formato SQL
- Backup para formato ZIP
- Configuração via `BackupConfig`
- Interface `ExporterInterface` para extensibilidade
- Interface `StorageInterface` para storages personalizados
- Suporte a nomes de arquivo personalizados
- Geração automática de nomes com timestamp
- Criação automática de diretórios
- Tratamento robusto de erros
- Testes unitários básicos
- Documentação inicial

### 📚 Documentação
- README.md com exemplos
- Comentários PHPDoc em todas as classes
- Exemplos de uso básico

---

## [Em Desenvolvimento]

### 🔮 Planejado para 1.2.0
- [ ] Suporte a AWS S3
- [ ] Suporte a FTP/SFTP
- [ ] Suporte a Dropbox
- [ ] Backup incremental
- [ ] Compressão personalizada
- [ ] Criptografia de backups
- [ ] Restauração automática
- [ ] Notificações via Slack/Discord/Telegram
- [ ] Dashboard web de monitoramento
- [ ] CLI commands (Symfony Console)

### 💡 Ideias Futuras
- [ ] Backup de tabelas específicas
- [ ] Exclusão de tabelas
- [ ] Backup apenas de estrutura
- [ ] Backup apenas de dados
- [ ] Suporte a múltiplos SGBDs (PostgreSQL, etc)
- [ ] Compressão diferencial
- [ ] Versionamento de backups

---

## Tipos de Mudanças

- **✨ Adicionado** - Novas funcionalidades
- **🔧 Modificado** - Mudanças em funcionalidades existentes
- **❌ Descontinuado** - Recursos que serão removidos
- **🗑️ Removido** - Recursos removidos
- **🐛 Corrigido** - Correções de bugs
- **🔒 Segurança** - Correções de vulnerabilidades

---

[1.1.0]: https://github.com/seu-usuario/mysql-backup/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/seu-usuario/mysql-backup/releases/tag/v1.0.0