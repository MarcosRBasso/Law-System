# Sistema de Gestão para Escritórios de Advocacia

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0-red)
![Docker](https://img.shields.io/badge/docker-ready-blue)

Um sistema completo de gestão jurídica desenvolvido em Laravel, oferecendo automação, controle financeiro, atendimento ao cliente e integração com tribunais.

## 🚀 Funcionalidades

### 📋 CRM (Customer Relationship Management)
- ✅ Cadastro de pessoas físicas e jurídicas
- ✅ Histórico completo de atendimentos
- ✅ Sistema de tags e classificações
- ✅ Importação de dados via CSV
- ✅ Portal do cliente com acesso seguro

### ⚖️ Gestão de Processos Jurídicos
- ✅ Cadastro completo de processos (número, tribunal, instância, fases)
- ✅ Acompanhamento automático de andamentos
- ✅ Importação automática de movimentações (PJe, e-Proc, SAJ)
- ✅ Controle de prazos com engine própria
- ✅ Notificações automáticas

### 📄 Gestão de Documentos
- ✅ Upload e versionamento de arquivos
- ✅ Geração automatizada via templates Blade
- ✅ Assinatura digital ICP-Brasil (Certisign/Serasa)
- ✅ Audit trail completo
- ✅ Controle de acesso granular

### 📅 Calendário e Prazos
- ✅ Cálculo automático de dias úteis e feriados
- ✅ Agendamento de audiências e prazos
- ✅ Notificações via e-mail (SMTP) e SMS (Twilio/Zenvia)
- ✅ Integração com calendários externos

### ⏱️ Time Tracking e Faturamento
- ✅ Registro de horas por advogado e processo
- ✅ Geração automática de faturas em PDF
- ✅ Emissão de boletos e NF-e
- ✅ Controle de produtividade

### 💰 Gestão Financeira
- ✅ Controle completo de receitas e despesas
- ✅ Importação de extratos bancários (OFX/CAMT)
- ✅ Conciliação bancária automática
- ✅ Relatórios de fluxo de caixa
- ✅ Dashboards gerenciais

### 📊 Relatórios e Analytics
- ✅ KPIs de produtividade e performance
- ✅ Tempo médio de tramitação
- ✅ Gráficos gerenciais interativos
- ✅ Exportações para PDF/Excel

## 🛠️ Stack Tecnológica

### Backend
- **PHP 8.2+** - Linguagem principal
- **Laravel 10+** - Framework web
- **MySQL/PostgreSQL** - Banco de dados principal
- **Redis** - Cache e filas
- **Elasticsearch** - Busca full-text

### Frontend
- **Blade Templates** - Engine de templates
- **Vue.js 3** - Framework JavaScript
- **Tailwind CSS** - Framework CSS
- **Inertia.js** - Stack moderno SPA

### DevOps e Infraestrutura
- **Docker & Docker Compose** - Containerização
- **Kubernetes** - Orquestração (produção)
- **GitHub Actions** - CI/CD
- **Prometheus + Grafana** - Monitoramento

### Integrações
- **PJe, e-Proc, SAJ** - Tribunais eletrônicos
- **Certisign/Serasa** - Assinatura digital
- **Twilio/Zenvia** - SMS
- **OAB API** - Validação de advogados

## 🚀 Instalação e Configuração

### Pré-requisitos
- Docker e Docker Compose
- Git
- Make (opcional)

### Instalação com Docker (Recomendado)

1. **Clone o repositório:**
```bash
git clone https://github.com/seu-usuario/sistema-juridico.git
cd sistema-juridico
```

2. **Configure o ambiente:**
```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

3. **Inicie os containers:**
```bash
docker-compose up -d
```

4. **Instale as dependências:**
```bash
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app npm run build
```

5. **Configure a aplicação:**
```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan storage:link
```

6. **Acesse a aplicação:**
- Frontend: http://localhost:8000
- API: http://localhost:8000/api
- Documentação: http://localhost:8000/api/docs
- Horizon (Filas): http://localhost:8000/horizon
- Grafana: http://localhost:3000

### Instalação Manual

1. **Requisitos do sistema:**
```bash
# PHP 8.2+ com extensões
php -m | grep -E "(pdo_mysql|redis|gd|zip|xml|mbstring|curl|json|openssl)"

# Composer
composer --version

# Node.js 18+
node --version
npm --version
```

2. **Clone e configure:**
```bash
git clone https://github.com/seu-usuario/sistema-juridico.git
cd sistema-juridico
composer install
npm install
cp .env.example .env
php artisan key:generate
```

3. **Configure banco de dados:**
```bash
# Crie o banco de dados
mysql -u root -p -e "CREATE DATABASE juridico_system;"

# Execute as migrations
php artisan migrate --seed
```

4. **Inicie os serviços:**
```bash
# Servidor web
php artisan serve

# Queue worker (nova aba)
php artisan queue:work

# Scheduler (configure no cron)
* * * * * cd /caminho-para-projeto && php artisan schedule:run >> /dev/null 2>&1
```

## 📖 Documentação da API

A documentação completa da API está disponível em:
- **Swagger UI**: http://localhost:8000/api/docs
- **OpenAPI 3.0**: [openapi.yaml](./openapi.yaml)
- **Postman Collection**: [docs/postman/](./docs/postman/)

### Autenticação

A API usa autenticação Bearer Token (Laravel Sanctum):

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Usar token nas requisições
curl -X GET http://localhost:8000/api/clients \
  -H "Authorization: Bearer {seu-token}"
```

### Endpoints Principais

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/auth/login` | Autenticação |
| GET | `/api/clients` | Listar clientes |
| POST | `/api/clients` | Criar cliente |
| GET | `/api/lawsuits` | Listar processos |
| POST | `/api/lawsuits` | Criar processo |
| GET | `/api/time-entries` | Listar horas |
| POST | `/api/time-entries/start` | Iniciar timer |
| GET | `/api/invoices` | Listar faturas |
| GET | `/api/financial/dashboard` | Dashboard financeiro |

## 🧪 Testes

### Executar todos os testes
```bash
# Com Docker
docker-compose exec app ./vendor/bin/pest

# Manual
./vendor/bin/pest
```

### Testes com cobertura
```bash
./vendor/bin/pest --coverage --min=80
```

### Testes específicos
```bash
# Testes unitários
./vendor/bin/pest tests/Unit

# Testes de feature
./vendor/bin/pest tests/Feature

# Teste específico
./vendor/bin/pest tests/Feature/ClientTest.php
```

## 🔧 Configuração Avançada

### Variáveis de Ambiente Importantes

```env
# Aplicação
APP_NAME="Sistema Jurídico"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://juridico.example.com

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=mysql-service
DB_DATABASE=juridico_system
DB_USERNAME=juridico
DB_PASSWORD=senha-segura

# Cache e Filas
REDIS_HOST=redis-service
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

# Integrações
CERTISIGN_API_KEY=sua-chave-certisign
TWILIO_SID=seu-sid-twilio
OAB_API_KEY=sua-chave-oab

# Monitoramento
SENTRY_LARAVEL_DSN=https://...
PROMETHEUS_ENABLED=true
```

### Configuração de Produção

1. **Otimizações:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

2. **Filas em produção:**
```bash
# Supervisor configuration
sudo nano /etc/supervisor/conf.d/juridico-worker.conf
```

3. **Backup automático:**
```bash
# Configure no cron
0 2 * * * php artisan backup:run --only-db
```

## 🚀 Deploy

### Deploy com Docker

```bash
# Build da imagem
docker build -t juridico/sistema-advocacia:latest .

# Push para registry
docker push juridico/sistema-advocacia:latest

# Deploy com Docker Compose
docker-compose -f docker-compose.prod.yml up -d
```

### Deploy no Kubernetes

```bash
# Apply das configurações
kubectl apply -f kubernetes/

# Verificar status
kubectl get pods -n juridico-system
kubectl get services -n juridico-system
```

### Deploy tradicional

```bash
# Upload dos arquivos
rsync -avz --exclude node_modules . user@servidor:/var/www/juridico/

# No servidor
cd /var/www/juridico
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔒 Segurança

### Principais medidas implementadas:
- ✅ Criptografia AES-256 para documentos sensíveis
- ✅ Autenticação multi-fator (2FA)
- ✅ Controle de acesso baseado em roles (RBAC)
- ✅ Audit trail completo
- ✅ Conformidade com LGPD
- ✅ Rate limiting nas APIs
- ✅ Validação de entrada rigorosa
- ✅ Headers de segurança HTTP

### Configurações de segurança:
```env
# Habilitar HTTPS
APP_URL=https://juridico.example.com
SESSION_SECURE_COOKIE=true

# Configurar rate limiting
API_RATE_LIMIT=60

# Habilitar criptografia de documentos
ENCRYPT_DOCUMENTS=true
```

## 📊 Monitoramento

### Métricas disponíveis:
- Performance da aplicação
- Uso de recursos (CPU, memória, disco)
- Filas e jobs
- Erros e exceções
- Logs de auditoria

### Dashboards Grafana:
- Dashboard principal do sistema
- Métricas de performance
- Monitoramento de filas
- Logs de segurança

### Alertas configurados:
- Falhas de sincronização com tribunais
- Prazos vencendo
- Erros críticos
- Uso excessivo de recursos

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

### Padrões de código:
- PSR-12 para PHP
- ESLint para JavaScript
- Conventional Commits para mensagens

## 📝 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 📞 Suporte

- **Documentação**: [Wiki do projeto](https://github.com/seu-usuario/sistema-juridico/wiki)
- **Issues**: [GitHub Issues](https://github.com/seu-usuario/sistema-juridico/issues)
- **Email**: suporte@juridico.example.com
- **Slack**: [Canal #suporte](https://juridico.slack.com)

## 🗺️ Roadmap

### Versão 1.1 (Q2 2024)
- [ ] Integração com WhatsApp Business
- [ ] App mobile (React Native)
- [ ] Relatórios avançados com BI
- [ ] Integração com bancos via Open Banking

### Versão 1.2 (Q3 2024)
- [ ] Inteligência artificial para análise de contratos
- [ ] Automação de petições
- [ ] Integração com cartórios eletrônicos
- [ ] Dashboard executivo avançado

### Versão 2.0 (Q4 2024)
- [ ] Arquitetura de microserviços
- [ ] Multi-tenancy
- [ ] API GraphQL
- [ ] Módulo de compliance

---

**Desenvolvido com ❤️ para a comunidade jurídica brasileira**