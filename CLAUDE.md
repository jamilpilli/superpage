# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Regras de Git para este projeto

**NUNCA faça commit direto na branch `main`.**

Antes de qualquer modificação:

```bash
git checkout main && git pull origin main
git checkout -b feature/nome-da-tarefa   # ou fix/ ou chore/
```

Ao terminar, abra um Pull Request e aguarde revisão antes do merge.

- Mensagens de commit em **português**, um commit por mudança lógica.

## Stack e Ambiente

- **PHP puro (Vanilla PHP)** — sem frameworks, sem Composer
- **MySQL** via PDO (configurado em `config/database.php`)
- **Tailwind CSS** via CDN (sem build step)
- **Ambiente local:** Laragon (Windows) — `DocumentRoot` apontado direto para a pasta do projeto, acessível via `http://localhost`
- **Produção:** Apache com `mod_rewrite` ativado (`.htaccess` faz o roteamento para `index.php`)
- Variáveis de ambiente em `.env` (não commitado) — veja `config/database.php` para as keys esperadas: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_DEBUG`

## Executar / Desenvolver

Não há build step. Para rodar localmente:

1. Instale o **Laragon** (laragon.org) e aponte o `Document Root` para a pasta do projeto
2. Copie `.env.example` → `.env` e preencha as credenciais do banco (MySQL local do Laragon: `root` sem senha)
3. Crie o banco de dados no phpMyAdmin (`localhost/phpmyadmin6/public`)
4. Execute as migrations em ordem via PowerShell:
```powershell
foreach ($f in Get-ChildItem migrations/*.sql | Sort-Object Name) { Get-Content $f.FullName | & "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root nome_do_banco }
```
5. Acesse `http://localhost`

## Migrations

Adicione arquivos `.sql` na pasta `migrations/` com prefixo numérico sequencial (ex: `010_nome.sql`).

**Opção preferida — `migrate.php`** (idempotente, rastreia execuções na tabela `schema_migrations`):
```bash
"C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" migrate.php
```

**Alternativa via PowerShell** (substituindo `nome_do_banco` pelo valor em `DB_NAME` do `.env`):
```powershell
foreach ($f in Get-ChildItem migrations/*.sql | Sort-Object Name) { Get-Content $f.FullName | & "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root nome_do_banco }
```

## Arquitetura

### Roteamento (Single Entry Point)

`index.php` → `router.php` — toda requisição passa por aqui via `.htaccess`.

**Prioridade de resolução em `index.php`:**
1. Subdomínio `hub.*` → `hub_index.php` (painel SuperAdmin, role `admin`)
2. Redirects 301 da tabela `redirects`
3. Domínios customizados → resolve para o slug do site do cliente
4. Roteamento interno via `router.php`:
   - `/` → `pages/public/home.php`
   - `/auth/*` → `pages/auth/*.php`
   - `/api/*` → `api/endpoints/*.php` (retorna JSON)
   - `/dashboard/*` → `pages/dashboard/*.php` (requer login)
   - `/hub/*` → `pages/hub/*.php` (requer login + role admin)
   - Qualquer outra URL → `pages/site/resolver.php` (renderiza o site OnePage do cliente)

### Modelo de Dados Principal

```
users → sites → pages → blocks
                │         └── config (JSON): configurações de cada bloco
                └── subscriptions
```

- **`blocks.type`**: `header`, `hero`, `about`, `services`, `products`, `testimonials`, `gallery`, `videos`, `contact`, `footer`
- **`blocks.config`**: JSON livre por tipo de bloco
- **`sites.design`**: JSON com `primary_color`, `title_font`, `text_font`, `button_style`
- **`site_analytics`**: registra visitas (exceto quando `?preview=true`)
- **`site_contacts`**: mensagens recebidas pelo formulário de contato
- **`redirects`**: mapeamentos 301 (`old_url` → `new_url`, campo `is_active`)
- **`rate_limits`**: throttle por IP e action (usado no formulário de contato do site)
- **`hub_audit_logs`**: log de ações administrativas (admin_id, action, description)
- **`subscriptions`** + **`themes`**: planos por site (MRR calculado no hub)

### Camadas

| Arquivo | Responsabilidade |
|---|---|
| `config/app.php` | Constantes globais (`APP_NAME`, `BASE_URL`, `UPLOAD_DIR`) |
| `config/database.php` | Cria `$pdo` global; lê `.env` manualmente |
| `includes/functions.php` | Helpers de DB: `db_fetch_all`, `db_fetch_one`, `db_insert`, `db_update` |
| `includes/helpers.php` | `generate_slug`, `redirect`, `get_upload_url`, `d`/`dd` para debug |
| `includes/auth.php` | `is_logged_in`, `require_login`, `require_role`, CSRF token |
| `includes/Storage.php` | Abstração de upload (`Storage::disk()->save/delete/getUrl`) |
| `includes/SimpleSMTP.php` | Envio de e-mail sem dependência externa |
| `includes/dashboard_template.php` | Layout do dashboard do cliente |
| `includes/hub_template.php` | Layout do HUB SuperAdmin |

### Roles de Usuário

- `client` — acesso ao `/dashboard` (gerencia seus próprios sites)
- `partner` — parceiros (acesso parcial ao hub)
- `admin` — acesso total, incluindo `/hub` e `hub.*` subdomínio

### Páginas do Dashboard

Todas em `pages/dashboard/`, requerem login. Passam `site_id` via query string quando referenciadas a um site específico.

- `index.php` — visão geral (métricas, lista de sites)
- `create_site.php` — criação de novo site
- `content.php` — editor de blocos (Alpine.js + SortableJS + `/api/blocks`)
- `contacts.php` — mensagens recebidas pelo formulário de contato
- `settings.php` — configurações da conta do usuário
- `site_settings.php` — configurações do site (slug, domínio customizado, status)

### API Endpoints

Arquivos em `api/endpoints/` respondem JSON. Acesso via `/api/{nome-do-arquivo}`.

- `blocks.php` — CRUD de blocos (usado pelo builder do dashboard)
- `upload.php` — upload de imagens (retorna URL pública)

### Renderização do Site OnePage

`pages/site/resolver.php` busca o site pelo slug, carrega os blocos ordenados por `sort_order`, e renderiza o HTML final com switch/case por tipo de bloco. O design global (cores, fontes) é aplicado via CSS custom properties.

### BASE_URL e Paths

`BASE_URL` é definido em `config/app.php` como string vazia em produção e Laragon (root). Em desenvolvimento com XAMPP com o projeto em subpasta `/superpage`, `BASE_URL` é automaticamente `/superpage`. Sempre use `BASE_URL` ao montar links internos em PHP (ex: `BASE_URL . '/dashboard'`).

### CSRF

Todo formulário mutante deve:
1. Incluir `<?= generate_csrf_token() ?>` como campo hidden
2. Verificar com `verify_csrf_token($_POST['csrf_token'] ?? '')` no handler

Nunca pular CSRF em endpoints de API que recebem POST — `api/endpoints/blocks.php` usa sessão para autenticação e também exige CSRF.

## Design System — Kinetic

A pasta `layouts/` contém os layouts de referência do redesign visual do sistema (design system "Kinetic"). Cada subpasta tem `code.html` (markup final) e `screen.png` (screenshot de referência).

| Layout | Página alvo |
|---|---|
| `layouts/dashboard_overview/` | `pages/dashboard/index.php` |
| `layouts/dashboard_contacts/` | `pages/dashboard/contacts.php` |
| `layouts/dashboard_edit_content/` | `pages/dashboard/content.php` |
| `layouts/dashboard_edit_design_modal/` | Modal de design (extrair em `includes/design_modal.php`) |
| `layouts/superpage_landing_page/` | `pages/public/home.php` |
| `layouts/superpage_kinetic/DESIGN.md` | Especificação completa do design system |

**Tokens visuais principais:**
- Fundo: `#0d0d1a` (surface-dim), camadas: `#121220` → `#1e1e2f`
- Primária: `#a9a4ff` (light purple), `#685ef7` (dim), `#914feb` (secondary)
- Tipografia: **Plus Jakarta Sans** (títulos) + **Inter** (corpo) via Google Fonts
- Ícones: Google Material Symbols Outlined
- Cards: glassmorphism (`rgba(24,24,40,0.6)` + `backdrop-blur: 20px`)
- Sem bordas sólidas — usar opacidade 10–15% quando necessário

**Estrutura do dashboard Kinetic:** sidebar fixa (64rem) + topbar flutuante + área de conteúdo com scroll. Bottom nav mobile fixo.