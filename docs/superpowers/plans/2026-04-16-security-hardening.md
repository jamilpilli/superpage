# Security Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corrigir as 9 vulnerabilidades de segurança identificadas na auditoria do projeto Superpage.

**Architecture:** Modificações cirúrgicas em arquivos existentes — sem novas dependências, sem mudança de stack. Cada tarefa é independente e pode ser commitada separadamente.

**Tech Stack:** Vanilla PHP, PDO/MySQL, Apache .htaccess

---

## Arquivos Modificados

| Arquivo | Mudança |
|---------|---------|
| `.htaccess` | Adicionar CSP, HSTS; ativar proteção de diretórios |
| `includes/auth.php` | Session cookie flags + timeout de inatividade |
| `pages/site/resolver.php` | CSS injection fix + CSRF no formulário de contato + rate limiting de contato |
| `pages/auth/forgot_password.php` | Hash do token + rate limiting |
| `pages/auth/reset_password.php` | Comparar contra token hasheado |
| `.env.example` | Criação do arquivo de documentação de variáveis |

---

## Task 1: HTTP Security Headers (`.htaccess`)

**Files:**
- Modify: `superpage/.htaccess`

**Problemas resolvidos:**
- Ausência de Content-Security-Policy (XSS mitigation)
- Ausência de HSTS (downgrade HTTP)
- Proteção de diretórios sensíveis comentada

- [ ] **Step 1: Criar branch de trabalho**

```bash
git checkout main && git pull origin main
git checkout -b fix/security-hardening
```

- [ ] **Step 2: Substituir `.htaccess` pelo conteúdo corrigido**

Substituir o conteúdo completo de `superpage/.htaccess` por:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirecionar para index.php se o arquivo ou diretório não existir
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set X-XSS-Protection "1; mode=block"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.tailwindcss.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: blob:; frame-src https://www.youtube.com https://www.youtube-nocookie.com; connect-src 'self'; object-src 'none'; base-uri 'self'"
</IfModule>

# Impedir acesso a diretórios e arquivos sensíveis
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<DirectoryMatch "^.*/(config|migrations|includes)/">
    Require all denied
</DirectoryMatch>
```

- [ ] **Step 3: Verificar manualmente**

Abrir o browser e checar com DevTools → Network → Response Headers que os novos headers aparecem.

Ou via curl:
```bash
curl -I http://localhost
```
Esperar ver `Content-Security-Policy`, `Strict-Transport-Security`, `Permissions-Policy` nos headers.

- [ ] **Step 4: Commit**

```bash
git add superpage/.htaccess
git commit -m "segurança: adicionar CSP, HSTS e Permissions-Policy no .htaccess"
```

---

## Task 2: Session Cookie Security + Timeout (`includes/auth.php`)

**Files:**
- Modify: `superpage/includes/auth.php`

**Problemas resolvidos:**
- Cookies de sessão sem HttpOnly/Secure/SameSite (roubo via JS ou rede)
- Sessões sem timeout (sessões comprometidas vivem para sempre)

- [ ] **Step 1: Substituir o bloco de `session_start()` em `auth.php`**

Localizar linhas 4–6 atuais:
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

Substituir por:
```php
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Session timeout: expirar após 30 minutos de inatividade
define('SESSION_TIMEOUT', 1800);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();
```

- [ ] **Step 2: Verificar manualmente**

Fazer login, esperar 31+ minutos, tentar acessar `/dashboard` — deve redirecionar para login.

Para testar cookies: DevTools → Application → Cookies → verificar que `PHPSESSID` tem flags `HttpOnly` e `SameSite=Strict`.

- [ ] **Step 3: Commit**

```bash
git add superpage/includes/auth.php
git commit -m "segurança: adicionar flags HttpOnly/Secure/SameSite no cookie de sessão e timeout de 30min"
```

---

## Task 3: CSS Injection Fix (`pages/site/resolver.php`)

**Files:**
- Modify: `superpage/pages/site/resolver.php` (linhas 71–90)

**Problema resolvido:**
- `htmlspecialchars()` não é escaping correto para contexto CSS — um usuário com acesso ao dashboard pode injetar CSS malicioso via `primary_color` ou nome de fonte.

- [ ] **Step 1: Adicionar validação de cor e fonte logo após a linha 74 do resolver.php**

Localizar o bloco atual (linhas 70–82):
```php
$design = json_decode($site['design'] ?? '{}', true) ?: [];
$primaryColor = $design['primary_color'] ?? '#4f46e5';
$titleFont = $design['title_font'] ?? 'Inter';
$textFont = $design['text_font'] ?? 'Inter';
$buttonStyle = $design['button_style'] ?? 'rounded';

// Mapeia o formato do botão pra classes nativas do Tailwind
$btnRadiusMap = [
    'square' => 'rounded-none',
    'rounded' => 'rounded-md',
    'rounded-full' => 'rounded-full'
];
$btnRadiusClass = $btnRadiusMap[$buttonStyle] ?? 'rounded-md';
```

Substituir por:
```php
$design = json_decode($site['design'] ?? '{}', true) ?: [];

// Validar cor: aceitar apenas formato #rrggbb ou #rgb
$rawColor = $design['primary_color'] ?? '#4f46e5';
$primaryColor = preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $rawColor) ? $rawColor : '#4f46e5';

// Validar fonte: aceitar apenas letras, números, espaços e hífens
$allowedFontPattern = '/^[a-zA-Z0-9 \-]+$/';
$rawTitleFont = $design['title_font'] ?? 'Inter';
$titleFont = preg_match($allowedFontPattern, $rawTitleFont) ? $rawTitleFont : 'Inter';
$rawTextFont = $design['text_font'] ?? 'Inter';
$textFont = preg_match($allowedFontPattern, $rawTextFont) ? $rawTextFont : 'Inter';

$buttonStyle = $design['button_style'] ?? 'rounded';

// Mapeia o formato do botão pra classes nativas do Tailwind
$btnRadiusMap = [
    'square' => 'rounded-none',
    'rounded' => 'rounded-md',
    'rounded-full' => 'rounded-full'
];
$btnRadiusClass = $btnRadiusMap[$buttonStyle] ?? 'rounded-md';
```

- [ ] **Step 2: Remover `htmlspecialchars()` desnecessário das variáveis CSS (linha ~138)**

Localizar:
```php
        :root {
            --color-primary: <?=htmlspecialchars($primaryColor)?>;
            --font-title: '<?= htmlspecialchars($titleFont)?>', sans-serif;
            --font-text: '<?= htmlspecialchars($textFont)?>', sans-serif;
        }
```

Substituir por (variáveis já são safe por validação no passo anterior):
```php
        :root {
            --color-primary: <?= $primaryColor ?>;
            --font-title: '<?= $titleFont ?>', sans-serif;
            --font-text: '<?= $textFont ?>', sans-serif;
        }
```

- [ ] **Step 3: Verificar manualmente**

No banco de dados, setar `design` de um site para `{"primary_color":"red; } body { display:none; /*","title_font":"Inter"}` e acessar o site. Deve renderizar com a cor padrão `#4f46e5` sem quebrar o CSS.

- [ ] **Step 4: Commit**

```bash
git add superpage/pages/site/resolver.php
git commit -m "segurança: validar primary_color e fontes contra CSS injection no resolver"
```

---

## Task 4: CSRF + Rate Limiting no Formulário de Contato (`pages/site/resolver.php`)

**Files:**
- Modify: `superpage/pages/site/resolver.php`

**Problemas resolvidos:**
- Formulário de contato público sem proteção CSRF
- Sem rate limiting — qualquer bot pode spammar `site_contacts`

**Nota:** O rate limiting reutiliza a tabela `rate_limits` já existente (usada no login).

- [ ] **Step 1: Substituir o bloco do formulário de contato (linhas 40–56)**

Localizar:
```php
// 3. Processar formulário de Contato, se houver
$contactSuccess = false;
$contactError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    try {
        db_insert('site_contacts', [
            'site_id' => $site['id'],
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'message' => trim($_POST['message'] ?? '')
        ]);
        $contactSuccess = "Sua mensagem foi enviada com sucesso!";
    } catch (\PDOException $e) {
        $contactError = "Ocorreu um erro ao enviar sua mensagem. Tente novamente.";
    }
}
```

Substituir por:
```php
// 3. Processar formulário de Contato, se houver
$contactSuccess = false;
$contactError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $visitorIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rateLimitAction = 'contact_' . $site['id'];

    // Verificar CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $contactError = "Sessão inválida. Por favor, recarregue a página e tente novamente.";
    } else {
        // Rate limit: max 5 envios por hora por IP por site
        $limit = db_fetch_one(
            "SELECT attempts, blocked_until FROM rate_limits WHERE ip_address = :ip AND action = :action",
            [':ip' => $visitorIp, ':action' => $rateLimitAction]
        );

        if ($limit && $limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) {
            $contactError = "Você enviou muitas mensagens. Tente novamente mais tarde.";
        } else {
            // Validar campos obrigatórios
            $name    = trim($_POST['name'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $phone   = trim($_POST['phone'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($name) || empty($message)) {
                $contactError = "Nome e mensagem são obrigatórios.";
            } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contactError = "Endereço de email inválido.";
            } else {
                try {
                    db_insert('site_contacts', [
                        'site_id' => $site['id'],
                        'name'    => $name,
                        'email'   => $email,
                        'phone'   => $phone,
                        'message' => $message,
                    ]);

                    // Atualizar rate limit
                    if ($limit) {
                        $attempts = $limit['attempts'] + 1;
                        $blocked  = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+1 hour')) : null;
                        db_update('rate_limits', ['attempts' => $attempts, 'blocked_until' => $blocked],
                            'ip_address = :ip AND action = :action', [':ip' => $visitorIp, ':action' => $rateLimitAction]);
                    } else {
                        db_insert('rate_limits', ['ip_address' => $visitorIp, 'action' => $rateLimitAction, 'attempts' => 1]);
                    }

                    $contactSuccess = "Sua mensagem foi enviada com sucesso!";
                } catch (\PDOException $e) {
                    $contactError = "Ocorreu um erro ao enviar sua mensagem. Tente novamente.";
                }
            }
        }
    }
}
```

- [ ] **Step 2: Adicionar campo hidden CSRF no HTML do formulário de contato**

Buscar no resolver.php o formulário de contato (bloco `contact`) e localizar o `<form` do bloco. Dentro do `<form`, antes do primeiro input, adicionar:

```html
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
```

Exemplo de onde inserir (dentro do switch/case do bloco `contact`, no `<form method="POST"`):
```html
<form method="POST" action="" class="...">
    <input type="hidden" name="contact_submit" value="1">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <!-- restante dos campos -->
```

- [ ] **Step 3: Verificar manualmente**

Acessar um site público, submeter o formulário de contato sem o token CSRF (manipulando via DevTools) — deve retornar erro "Sessão inválida".

Submeter 6 vezes seguidas — na 6ª deve aparecer "Você enviou muitas mensagens."

- [ ] **Step 4: Commit**

```bash
git add superpage/pages/site/resolver.php
git commit -m "segurança: adicionar CSRF e rate limiting no formulário de contato público"
```

---

## Task 5: Hash do Token de Reset de Senha

**Files:**
- Modify: `superpage/pages/auth/forgot_password.php` (linha 27–31)
- Modify: `superpage/pages/auth/reset_password.php` (linha 20)

**Problema resolvido:**
- Tokens de reset armazenados em plaintext no banco — se o banco for comprometido, todos os tokens ficam expostos e podem ser usados imediatamente.

**Estratégia:** Gerar token aleatório → enviar token bruto por email → armazenar `hash('sha256', $token)` no banco → na validação, comparar `hash('sha256', $token_da_url)` contra o hash armazenado.

- [ ] **Step 1: Modificar `forgot_password.php` — armazenar hash do token**

Localizar (linhas 24–31):
```php
$token      = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

db_insert('password_resets', [
    'user_id'    => $user['id'],
    'token'      => $token,
    'expires_at' => $expires_at
]);
```

Substituir por:
```php
$token      = bin2hex(random_bytes(32));
$tokenHash  = hash('sha256', $token);
$expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

db_insert('password_resets', [
    'user_id'    => $user['id'],
    'token'      => $tokenHash,
    'expires_at' => $expires_at
]);
```

O `$token` (bruto) continua sendo enviado no link por email — sem mudança no email.

- [ ] **Step 2: Modificar `reset_password.php` — comparar hash**

Localizar (linha 20):
```php
$resetRecord = db_fetch_one("SELECT user_id, expires_at FROM password_resets WHERE token = :token", [':token' => $token]);
```

Substituir por:
```php
$tokenHash   = hash('sha256', $token);
$resetRecord = db_fetch_one("SELECT user_id, expires_at FROM password_resets WHERE token = :token", [':token' => $tokenHash]);
```

- [ ] **Step 3: Limpar tokens antigos do banco**

Como os tokens antigos estão em plaintext e os novos em sha256, eles são incompatíveis. Executar no banco para limpar:

```sql
DELETE FROM password_resets WHERE expires_at < NOW();
```

(Os tokens válidos em plaintext deixarão de funcionar — usuários com reset pendente terão que solicitar novo link. Duração máxima é 30min, então o impacto é mínimo.)

- [ ] **Step 4: Verificar manualmente**

1. Solicitar reset de senha para um email existente
2. No banco (`password_resets`), verificar que o campo `token` contém uma string de 64 caracteres hexadecimais (sha256)
3. Clicar no link do email e completar o reset — deve funcionar normalmente

- [ ] **Step 5: Commit**

```bash
git add superpage/pages/auth/forgot_password.php superpage/pages/auth/reset_password.php
git commit -m "segurança: armazenar hash sha256 do token de reset em vez de plaintext"
```

---

## Task 6: Rate Limiting no Forgot Password (`pages/auth/forgot_password.php`)

**Files:**
- Modify: `superpage/pages/auth/forgot_password.php`

**Problema resolvido:**
- Sem rate limiting, qualquer IP pode spammar emails de reset para qualquer endereço ilimitadamente.

- [ ] **Step 1: Adicionar verificação de rate limit antes do envio de email**

Localizar (linha 14 em diante), o bloco `if ($_SERVER['REQUEST_METHOD'] === 'POST')`:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
```

Substituir por:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Rate limit: max 3 tentativas por hora por IP
        $limit = db_fetch_one(
            "SELECT attempts, blocked_until FROM rate_limits WHERE ip_address = :ip AND action = 'forgot_password'",
            [':ip' => $ip]
        );

        if ($limit && $limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) {
            $success = "If that email is registered, you'll receive a reset link shortly.";
        } else {
            // Atualizar contador antes de processar (para evitar enumeração de email via timing)
            if ($limit) {
                $attempts = $limit['attempts'] + 1;
                $blocked  = $attempts >= 3 ? date('Y-m-d H:i:s', strtotime('+1 hour')) : null;
                db_update('rate_limits', ['attempts' => $attempts, 'blocked_until' => $blocked],
                    'ip_address = :ip AND action = \'forgot_password\'', [':ip' => $ip]);
            } else {
                db_insert('rate_limits', ['ip_address' => $ip, 'action' => 'forgot_password', 'attempts' => 1]);
            }

        $email = trim($_POST['email'] ?? '');
```

E adicionar o fechamento do novo bloco `else` antes do `}` final do POST handler. Localizar o fechamento atual:
```php
        } else {
            $error = "Invalid email address.";
        }
    }
}
```

Substituir por:
```php
        } else {
            $error = "Invalid email address.";
        }
        } // fecha o bloco de rate limit
    }
}
```

- [ ] **Step 2: Verificar manualmente**

Solicitar reset de senha 4 vezes com o mesmo IP → na 4ª tentativa, verificar que a mensagem retornada é a genérica (sem erro explícito de rate limit para não revelar o mecanismo).

Verificar na tabela `rate_limits` que existe um registro `action = 'forgot_password'` para o IP.

- [ ] **Step 3: Commit**

```bash
git add superpage/pages/auth/forgot_password.php
git commit -m "segurança: adicionar rate limiting (3/hora por IP) no endpoint de forgot password"
```

---

## Task 7: Criar `.env.example`

**Files:**
- Create: `superpage/.env.example`

**Problema resolvido:**
- Novos desenvolvedores ou servidores não sabem quais variáveis de ambiente são necessárias.
- Documenta que `APP_DEBUG=false` em produção.

- [ ] **Step 1: Criar o arquivo `.env.example`**

```bash
# Copiar .env existente como base — NÃO commitar o .env real
```

Conteúdo do arquivo `superpage/.env.example`:

```dotenv
# ============================================================
# Superpage — variáveis de ambiente
# Copie este arquivo para .env e preencha os valores reais.
# NUNCA commite o arquivo .env no repositório.
# ============================================================

# Banco de Dados (MySQL)
DB_HOST=127.0.0.1
DB_NAME=superpagebd
DB_USER=root
DB_PASS=

# Modo de depuração
# ATENÇÃO: definir como false em produção para não expor stack traces
APP_DEBUG=false

# Chave da API Resend (https://resend.com) para envio de emails
# Obter em: https://resend.com/api-keys
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxx

# (Opcional) Configuração SMTP alternativa
# SMTP_HOST=smtp.example.com
# SMTP_PORT=587
# SMTP_USER=user@example.com
# SMTP_PASS=senha
```

- [ ] **Step 2: Verificar que `.env.example` NÃO está no `.gitignore`**

```bash
cat superpage/.gitignore | grep env
```

Esperar ver `.env` ignorado mas `.env.example` não.

- [ ] **Step 3: Commit**

```bash
git add superpage/.env.example
git commit -m "segurança: criar .env.example documentando todas as variáveis necessárias"
```

---

## Task 8: APP_DEBUG em Produção

**Não é uma mudança de código — é uma mudança de configuração no servidor.**

- [ ] **Step 1: No servidor de produção, editar o arquivo `.env`**

```bash
ssh root@2a02:4780:f:5dbb::1
docker exec -it $(docker ps --filter "name=wordpress_bandpage" -q) bash
# Dentro do container:
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' /var/www/backend/.env
# Verificar:
grep APP_DEBUG /var/www/backend/.env
```

- [ ] **Step 2: Verificar**

Acessar uma URL inválida do app em produção → deve mostrar mensagem genérica "Erro interno de conexão com o banco de dados." sem stack trace.

---

## Checklist Final

Após todas as tasks:

- [ ] `APP_DEBUG=false` no servidor de produção
- [ ] Headers de segurança visíveis via `curl -I https://seudominio.com`
- [ ] Cookie de sessão com `HttpOnly` e `SameSite=Strict` no browser
- [ ] Token de reset aparece como hash sha256 (64 chars hex) na tabela `password_resets`
- [ ] Formulário de contato retorna erro se CSRF token ausente/inválido
- [ ] Rate limiting no forgot_password registra entradas em `rate_limits`
- [ ] `.env.example` commitado no repositório
- [ ] PR aberto para a branch `fix/security-hardening`
