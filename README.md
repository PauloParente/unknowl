# 🚀 Laravel Community Platform

Uma plataforma moderna de comunidades estilo Reddit construída com Laravel 12, Vue 3, Inertia.js e Tailwind CSS. Inclui sistema de chat em tempo real, moderação avançada, sistema de votação e busca inteligente.

## ✨ Funcionalidades

### 🏘️ **Comunidades**
- Criação e gerenciamento de comunidades
- Sistema de moderação com roles (Owner, Admin, Moderator)
- Regras personalizáveis e aprovação de membros
- Avatares e capas personalizáveis
- Posts fixados e sistema de votação

### 💬 **Chat em Tempo Real**
- Chat privado e em grupo
- Mensagens em tempo real com Pusher
- Indicadores de leitura
- Histórico de mensagens

### 🔍 **Busca Inteligente**
- Busca unificada em comunidades, posts e usuários
- Autocomplete em tempo real
- Filtros avançados
- Navegação por teclado

### 🗳️ **Sistema de Votação**
- Like/Dislike em posts e comentários
- Sistema de score automático
- Rate limiting e validações de segurança
- Prevenção de auto-voto

### 👥 **Usuários e Autenticação**
- Sistema completo de autenticação com Laravel Fortify
- Perfis de usuário personalizáveis
- Configurações de aparência
- Autenticação de dois fatores

## 🛠️ Stack Tecnológica

### Backend
- **Laravel 12** - Framework PHP
- **PHP 8.4** - Linguagem de programação
- **SQLite** - Banco de dados (desenvolvimento)
- **Laravel Reverb** - WebSockets
- **Pusher** - Real-time messaging
- **Laravel Fortify** - Autenticação

### Frontend
- **Vue 3** - Framework JavaScript
- **Inertia.js v2** - SPA sem API
- **Tailwind CSS v4** - Framework CSS
- **TypeScript** - Tipagem estática
- **Vite** - Build tool

### Ferramentas de Desenvolvimento
- **Pest** - Framework de testes
- **Laravel Pint** - Code formatter
- **ESLint** - Linter JavaScript
- **Prettier** - Code formatter
- **Laravel Wayfinder** - Development tools

## 🚀 Instalação

### Pré-requisitos
- **PHP 8.2+** (8.4 recomendado)
- Composer
- Node.js 18+
- Laravel Herd (recomendado)

> ⚠️ **Importante**: Laravel 12 requer PHP 8.2 ou superior. Se você estiver usando PHP 7.4, atualize antes de continuar.

### 1. Clone o repositório
```bash
git clone https://github.com/seu-usuario/laravel-community-platform.git
cd laravel-community-platform
```

### 2. Instale as dependências
```bash
composer install
npm install
```

### 3. Configure o ambiente
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure o banco de dados
```bash
# SQLite (desenvolvimento)
touch database/database.sqlite

# Ou configure MySQL/PostgreSQL no .env
```

### 5. Execute as migrações
```bash
php artisan migrate
```

### 6. Configure o Pusher (opcional)
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

### 7. Inicie o servidor de desenvolvimento
```bash
composer run dev
```

O projeto estará disponível em `https://myapp.test` (Laravel Herd) ou `http://localhost:8000`.

## 🧪 Testes

### Executar todos os testes
```bash
php artisan test
```

### Executar testes específicos
```bash
php artisan test --filter=CommunityTest
php artisan test tests/Feature/ChatControllerTest.php
```

### Testes com cobertura
```bash
php artisan test --coverage
```

## 📁 Estrutura do Projeto

```
├── app/
│   ├── Enums/              # Enums do sistema
│   ├── Events/             # Eventos de broadcast
│   ├── Http/
│   │   ├── Controllers/    # Controllers da aplicação
│   │   ├── Middleware/     # Middlewares customizados
│   │   └── Requests/       # Form Requests de validação
│   ├── Models/             # Modelos Eloquent
│   ├── Policies/            # Políticas de autorização
│   └── Services/            # Serviços da aplicação
├── database/
│   ├── factories/           # Factories para testes
│   ├── migrations/          # Migrações do banco
│   └── seeders/             # Seeders para dados iniciais
├── docs/                    # Documentação do projeto
├── resources/
│   ├── js/
│   │   ├── components/      # Componentes Vue reutilizáveis
│   │   ├── composables/     # Composables Vue
│   │   ├── layouts/         # Layouts da aplicação
│   │   ├── pages/           # Páginas Inertia
│   │   └── types/           # Tipos TypeScript
│   └── css/                 # Estilos CSS
├── routes/                  # Definição de rotas
└── tests/                   # Testes automatizados
```

## 🎨 Design System

O projeto utiliza um design system consistente baseado em:

- **Tailwind CSS v4** - Utility-first CSS framework
- **shadcn/vue** - Componentes UI reutilizáveis
- **Lucide Icons** - Ícones modernos
- **Dark Mode** - Suporte completo a tema escuro
- **Responsive Design** - Mobile-first approach

### Cores do Sistema
- **Comunidades**: Azul (`blue-600`)
- **Posts**: Verde (`green-600`)
- **Usuários**: Roxo (`purple-600`)
- **Chat**: Laranja (`orange-600`)

## 🔧 Comandos Úteis

### Desenvolvimento
```bash
# Iniciar servidor de desenvolvimento
composer run dev

# Iniciar com SSR
composer run dev:ssr

# Build para produção
npm run build
```

### Banco de Dados
```bash
# Reset do banco
php artisan migrate:fresh --seed

# Criar nova migração
php artisan make:migration create_example_table

# Criar factory
php artisan make:factory ExampleFactory
```

### Testes
```bash
# Executar testes
php artisan test

# Criar teste
php artisan make:test ExampleTest
```

### Code Quality
```bash
# Formatar código PHP
vendor/bin/pint

# Formatar código JavaScript
npm run format

# Lint JavaScript
npm run lint
```

## 🚨 Troubleshooting

### Problema: PHP Version Error
Se você receber o erro `Your Composer dependencies require a PHP version ">= 8.2.0". You are running 7.4.33`:

1. **Com Laravel Herd:**
   ```bash
   herd use php-8.4
   ```

2. **Verificar versão:**
   ```bash
   php --version
   ```

3. **Se ainda usar PHP 7.4:**
   - Instale PHP 8.4+ manualmente
   - Configure no PATH do Windows
   - Reinicie o terminal

### Problema: CI/CD Failures
As falhas no GitHub Actions são causadas por:
- Incompatibilidade de versão do PHP
- Testes falhando devido ao PHP 7.4

**Solução:** Atualize para PHP 8.2+ e os testes passarão.

## 📚 Documentação

- [Funcionalidade de Busca](docs/search-functionality.md)
- [Configuração do Pusher](docs/pusher-setup.md)
- [Middleware de Votação](docs/vote-middleware.md)
- [Mudanças no Header](docs/header-layout-changes.md)

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Padrões de Código
- Siga os padrões do Laravel
- Use TypeScript para JavaScript
- Escreva testes para novas funcionalidades
- Use commits semânticos

## 🐛 Reportar Bugs

Use o [GitHub Issues](https://github.com/seu-usuario/laravel-community-platform/issues) para reportar bugs. Inclua:

- Descrição detalhada do problema
- Passos para reproduzir
- Screenshots (se aplicável)
- Informações do ambiente

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🙏 Agradecimentos

- [Laravel](https://laravel.com) - Framework PHP
- [Vue.js](https://vuejs.org) - Framework JavaScript
- [Inertia.js](https://inertiajs.com) - SPA framework
- [Tailwind CSS](https://tailwindcss.com) - CSS framework
- [Pusher](https://pusher.com) - Real-time messaging

---

**Desenvolvido com ❤️ usando Laravel 12 e Vue 3**