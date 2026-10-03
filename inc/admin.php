<?php
/**
 * PLACATA /mzcentral — sessão, login, CSRF, limite de tentativas e layout do painel.
 * Padrões herdados do Laboratório Brasília (auth.php/csrf.php), com o limite de
 * tentativas gravado no BANCO (por IP + e-mail) em vez da sessão — a sessão o
 * atacante descarta; a tabela não.
 */

declare(strict_types=1);

const LOGIN_MAX_TENTATIVAS = 5;      // por IP + e-mail
const LOGIN_MAX_POR_IP = 20;         // por IP (qualquer e-mail)
const LOGIN_JANELA_MIN = 15;
const SESSAO_OCIOSA_SEG = 7200;      // 2h sem uso = sai sozinho

/** Cabeçalhos de segurança de toda página do painel. */
function admin_cabecalhos(): void
{
    header('X-Robots-Tag: noindex, nofollow', true);
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
}

function https_ativo(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

/** Inicia a sessão com cookie HttpOnly / Secure / SameSite=Lax. */
function admin_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $seguro = (bool)cfg_local('cookie_seguro', true) || https_ativo();
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)SESSAO_OCIOSA_SEG);
    session_name('placata_adm');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/mzcentral/',
        'secure' => $seguro,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Expira sessão ociosa.
    if (!empty($_SESSION['admin_id'])) {
        $ultimo = (int)($_SESSION['ultimo_uso'] ?? 0);
        if ($ultimo && time() - $ultimo > SESSAO_OCIOSA_SEG) {
            admin_sair();
            session_start();
        }
    }
    $_SESSION['ultimo_uso'] = time();
}

function admin_logado(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Toda página interna chama isto: sem login -> volta para o login. */
function exigir_login(): void
{
    admin_cabecalhos();
    admin_sessao();
    if (!admin_logado()) {
        redirecionar('/mzcentral/');
    }
    // Confere se o admin ainda existe/está ativo.
    try {
        $st = db()->prepare('SELECT id FROM admins WHERE id = ? AND ativo = 1');
        $st->execute([(int)$_SESSION['admin_id']]);
        if (!$st->fetchColumn()) {
            admin_sair();
            redirecionar('/mzcentral/');
        }
    } catch (Throwable $e) {
        admin_erro_banco();
    }
}

function admin_sair(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'secure' => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }
}

// ---------------- CSRF ----------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

/** Chamar no início de TODO POST. Token ausente/errado -> 400 e para. */
function csrf_verificar(): void
{
    $enviado = (string)($_POST['csrf'] ?? '');
    $esperado = (string)($_SESSION['csrf'] ?? '');
    if ($enviado === '' || $esperado === '' || !hash_equals($esperado, $enviado)) {
        http_response_code(400);
        header('Content-Type: text/html; charset=utf-8');
        exit('<!doctype html><meta charset="utf-8"><title>Sessão expirada</title><p style="font-family:sans-serif;padding:2rem">A página ficou aberta por muito tempo ou o formulário é inválido. <a href="/mzcentral/">Volte ao painel</a> e tente de novo.</p>');
    }
}

// ---------------- Limite de tentativas de login ----------------

function ip_cliente(): string
{
    // REMOTE_ADDR apenas: cabeçalhos X-Forwarded-For podem ser forjados.
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function login_bloqueado(PDO $pdo, string $ip, string $email): bool
{
    $desde = date('Y-m-d H:i:s', time() - LOGIN_JANELA_MIN * 60);
    $st = $pdo->prepare('SELECT COUNT(*) FROM tentativas_login WHERE ip = ? AND email = ? AND criado_em >= ?');
    $st->execute([$ip, $email, $desde]);
    if ((int)$st->fetchColumn() >= LOGIN_MAX_TENTATIVAS) {
        return true;
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM tentativas_login WHERE ip = ? AND criado_em >= ?');
    $st->execute([$ip, $desde]);
    return (int)$st->fetchColumn() >= LOGIN_MAX_POR_IP;
}

function login_registrar_falha(PDO $pdo, string $ip, string $email): void
{
    $pdo->prepare('INSERT INTO tentativas_login (ip, email, criado_em) VALUES (?, ?, ?)')
        ->execute([$ip, $email, date('Y-m-d H:i:s')]);
    // Faxina: apaga registros com mais de 1 dia.
    $pdo->prepare('DELETE FROM tentativas_login WHERE criado_em < ?')
        ->execute([date('Y-m-d H:i:s', time() - 86400)]);
}

function login_limpar(PDO $pdo, string $ip, string $email): void
{
    $pdo->prepare('DELETE FROM tentativas_login WHERE ip = ? AND email = ?')->execute([$ip, $email]);
}

// ---------------- Mensagens (flash) ----------------

function avisar(string $texto, string $tipo = 'ok'): void
{
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo === 'erro' ? 'erro' : 'ok'];
}

function mostrar_aviso(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div role="status" class="alerta ' . h($f['tipo']) . '">' . h($f['texto']) . '</div>';
}

function admin_erro_banco(): void
{
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    exit('<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>Painel indisponível</title><p style="font-family:sans-serif;padding:2rem">Não foi possível acessar o banco de dados agora. Tente de novo em alguns minutos. Se continuar, avise o suporte da Marenzo.</p>');
}

// ---------------- Layout ----------------

function admin_inicio(string $titulo, string $ativo = ''): void
{
    $v = PLACATA_ASSET_V;
    $menu = [
        'painel' => ['/mzcentral/painel.php', 'Início'],
        'produtos' => ['/mzcentral/produtos.php', 'Produtos'],
        'textos' => ['/mzcentral/textos.php', 'Textos do topo'],
        'contato' => ['/mzcentral/contato.php', 'Contato'],
        'faq' => ['/mzcentral/faq.php', 'Perguntas frequentes'],
        'senha' => ['/mzcentral/senha.php', 'Trocar minha senha'],
    ];
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($titulo) ?> · PLACATA /mzcentral</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= $v ?>">
  <link rel="stylesheet" href="/assets/css/mzcentral.css?v=<?= $v ?>">
  <script src="/assets/js/mzcentral.js?v=<?= $v ?>" defer></script>
</head>
<body class="adm">
  <header class="adm-topo">
    <div class="wrap">
      <a href="/mzcentral/painel.php" aria-label="Início do painel"><img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="129" height="26"></a>
      <div class="adm-usuario">
        <span><?= h($_SESSION['admin_email'] ?? '') ?></span>
        <a class="adm-btn pequeno" href="/" target="_blank" rel="noopener">Ver site</a>
        <form method="post" action="/mzcentral/sair.php" class="form-inline"><?= csrf_campo() ?><button type="submit" class="adm-btn pequeno">Sair</button></form>
      </div>
    </div>
  </header>
  <main class="adm-main">
    <div class="wrap">
      <nav class="adm-nav" aria-label="Menu do painel">
<?php foreach ($menu as $chave => [$href, $rotulo]): ?>
        <a href="<?= $href ?>"<?= $chave === $ativo ? ' aria-current="page"' : '' ?>><?= h($rotulo) ?></a>
<?php endforeach; ?>
      </nav>
      <?= mostrar_aviso() ?>
<?php
}

function admin_fim(): void
{
    ?>
    </div>
  </main>
</body>
</html>
<?php
}
