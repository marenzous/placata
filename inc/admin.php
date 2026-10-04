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
        $st = db()->prepare('SELECT senha_hash FROM admins WHERE id = ? AND ativo = 1');
        $st->execute([(int)$_SESSION['admin_id']]);
        $hash = $st->fetchColumn();
        // Senha trocada/redefinida depois deste login -> esta sessão não vale mais.
        if ($hash === false || !hash_equals(admin_senha_marca((string)$hash), (string)($_SESSION['senha_marca'] ?? ''))) {
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

// ---------------- Marca da senha (derruba sessões antigas) ----------------

/**
 * Impressão digital do hash da senha guardada na sessão. Se a senha mudar
 * (troca ou redefinição por e-mail), toda sessão aberta antes deixa de bater
 * e é encerrada no próximo clique (ver exigir_login).
 */
function admin_senha_marca(string $senhaHash): string
{
    return substr(hash('sha256', 'placata-sessao|' . $senhaHash), 0, 32);
}

/** Mesmo critério de senha do painel inteiro (senha.php e redefinir.php). */
const SENHA_MINIMO = 10;

/** @return string[] lista de erros (vazia = senha aceita) */
function senha_nova_erros(string $nova, string $confirma): array
{
    if (mb_strlen($nova) < SENHA_MINIMO) {
        return ['A nova senha precisa ter pelo menos ' . SENHA_MINIMO . ' caracteres.'];
    }
    if ($nova !== $confirma) {
        return ['A confirmação não é igual à nova senha.'];
    }
    return [];
}

// ---------------- Esqueci minha senha ----------------

const REDEF_VALIDADE_MIN = 30;   // link vale 30 minutos, uma vez só
const REDEF_JANELA_MIN = 60;
const REDEF_MAX_POR_EMAIL = 3;   // pedidos por e-mail por hora
const REDEF_MAX_POR_IP = 10;     // pedidos + links errados por IP por hora

/**
 * Cria as tabelas do fluxo na primeira vez que ele roda (o site não tem
 * executor de migration). Mesmo SQL de instalar/schema.sql.
 */
function redefinicao_garantir_tabelas(PDO $pdo): void
{
    static $feito = false;
    if ($feito) {
        return;
    }
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS redefinicao_senha (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY redefinicao_token (token_hash),
  KEY redefinicao_admin (admin_id, usado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS tentativas_redefinicao (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  tipo VARCHAR(10) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY tentativas_redef_email (email, tipo, criado_em),
  KEY tentativas_redef_ip (ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $feito = true;
}

/** @return array{0:string,1:string} [token que vai no link, hash sha256 que vai pro banco] */
function redefinicao_gerar_token(): array
{
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    return [$token, redefinicao_hash($token)];
}

function redefinicao_hash(string $token): string
{
    return hash('sha256', $token);
}

function redefinicao_token_formato_ok(string $token): bool
{
    return (bool)preg_match('/^[A-Za-z0-9_-]{43}$/', $token);
}

/** Linha da tabela ainda vale? (não usada e dentro do prazo) */
function redefinicao_valido(array $linha, int $agora): bool
{
    if (!empty($linha['usado_em'])) {
        return false;
    }
    $exp = strtotime((string)($linha['expira_em'] ?? ''));
    return $exp !== false && $exp > $agora;
}

/** Limite: 3 pedidos por e-mail/hora e 10 registros (pedido ou link errado) por IP/hora. */
function redefinicao_bloqueada(PDO $pdo, string $ip, ?string $email): bool
{
    $desde = date('Y-m-d H:i:s', time() - REDEF_JANELA_MIN * 60);
    $st = $pdo->prepare('SELECT COUNT(*) FROM tentativas_redefinicao WHERE ip = ? AND criado_em >= ?');
    $st->execute([$ip, $desde]);
    if ((int)$st->fetchColumn() >= REDEF_MAX_POR_IP) {
        return true;
    }
    if ($email !== null && $email !== '') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM tentativas_redefinicao WHERE email = ? AND tipo = 'pedido' AND criado_em >= ?");
        $st->execute([$email, $desde]);
        return (int)$st->fetchColumn() >= REDEF_MAX_POR_EMAIL;
    }
    return false;
}

/** $tipo: 'pedido' (pediu link) ou 'token' (abriu link errado/vencido). */
function redefinicao_registrar(PDO $pdo, string $ip, string $email, string $tipo): void
{
    $pdo->prepare('INSERT INTO tentativas_redefinicao (ip, email, tipo, criado_em) VALUES (?, ?, ?, ?)')
        ->execute([$ip, mb_substr($email, 0, 190), $tipo === 'pedido' ? 'pedido' : 'token', date('Y-m-d H:i:s')]);
    $pdo->prepare('DELETE FROM tentativas_redefinicao WHERE criado_em < ?')
        ->execute([date('Y-m-d H:i:s', time() - 86400)]);
}

/** Gera link novo para o admin, invalidando os anteriores. Retorna o token em claro (só vai no e-mail). */
function redefinicao_criar(PDO $pdo, int $adminId, string $ip): string
{
    [$token, $hash] = redefinicao_gerar_token();
    $agora = date('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE redefinicao_senha SET usado_em = ? WHERE admin_id = ? AND usado_em IS NULL')
            ->execute([$agora, $adminId]);
        $pdo->prepare('INSERT INTO redefinicao_senha (admin_id, token_hash, expira_em, ip, criado_em) VALUES (?, ?, ?, ?, ?)')
            ->execute([$adminId, $hash, date('Y-m-d H:i:s', time() + REDEF_VALIDADE_MIN * 60), $ip, $agora]);
        // Faxina: links com mais de 1 dia.
        $pdo->prepare('DELETE FROM redefinicao_senha WHERE criado_em < ?')
            ->execute([date('Y-m-d H:i:s', time() - 86400)]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $token;
}

/** Link válido -> dados (id, admin_id, email); qualquer outra coisa -> null. */
function redefinicao_buscar(PDO $pdo, string $token): ?array
{
    if (!redefinicao_token_formato_ok($token)) {
        return null;
    }
    $st = $pdo->prepare('SELECT r.id, r.admin_id, r.expira_em, r.usado_em, a.email
        FROM redefinicao_senha r JOIN admins a ON a.id = r.admin_id AND a.ativo = 1
        WHERE r.token_hash = ? LIMIT 1');
    $st->execute([redefinicao_hash($token)]);
    $linha = $st->fetch();
    if (!$linha || !redefinicao_valido($linha, time())) {
        return null;
    }
    return $linha;
}

/**
 * Grava a nova senha e queima o link. O UPDATE condicional garante uso único
 * mesmo com dois cliques ao mesmo tempo. false = link inválido/vencido/usado.
 */
function redefinicao_concluir(PDO $pdo, string $token, string $novaSenha): bool
{
    $linha = redefinicao_buscar($pdo, $token);
    if (!$linha) {
        return false;
    }
    $agora = date('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('UPDATE redefinicao_senha SET usado_em = ? WHERE id = ? AND usado_em IS NULL AND expira_em > ?');
        $st->execute([$agora, (int)$linha['id'], $agora]);
        if ($st->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }
        $pdo->prepare('UPDATE admins SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($novaSenha, PASSWORD_DEFAULT), (int)$linha['admin_id']]);
        $pdo->prepare('UPDATE redefinicao_senha SET usado_em = ? WHERE admin_id = ? AND usado_em IS NULL')
            ->execute([$agora, (int)$linha['admin_id']]);
        // Quem acabou de provar que é dono do e-mail não fica preso no limite de login.
        $pdo->prepare('DELETE FROM tentativas_login WHERE email = ?')->execute([(string)$linha['email']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return true;
}

function redefinicao_link(string $token): string
{
    return PLACATA_URL . '/mzcentral/redefinir.php?token=' . rawurlencode($token);
}

function redefinicao_email_corpo(string $link): string
{
    return "Olá!\n\n"
        . "Recebemos um pedido para criar uma nova senha de acesso ao painel do site PLACATA.\n\n"
        . "Para criar a nova senha, abra o link abaixo:\n\n"
        . $link . "\n\n"
        . 'O link vale por ' . REDEF_VALIDADE_MIN . " minutos e só pode ser usado uma vez.\n\n"
        . "Se não foi você que pediu, pode ignorar esta mensagem: sua senha atual continua a mesma.\n\n"
        . "Site PLACATA (placata.com.br)\n";
}

/**
 * Envio de e-mail isolado aqui, para trocar por SMTP no futuro sem mexer nas telas.
 * Hoje: mail() do PHP (no Plesk entrega pelo sendmail local).
 * config.local.php pode ter 'email_remetente' (padrão no-reply@placata.com.br) e,
 * só para teste no computador, 'email_arquivo_teste' (grava a mensagem num arquivo em vez de enviar).
 */
function enviar_email(string $para, string $assunto, string $corpo): bool
{
    $para = trim($para);
    if ($para === '' || preg_match('/[\r\n]/', $para . $assunto) || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $remetente = (string)cfg_local('email_remetente', 'no-reply@placata.com.br');
    if (!filter_var($remetente, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $assuntoMime = mb_encode_mimeheader($assunto, 'UTF-8', 'B', "\r\n");
    $cabecalhos = [
        'From' => 'PLACATA <' . $remetente . '>',
        'Reply-To' => $remetente,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer' => 'PLACATA mzcentral',
    ];
    $arquivoTeste = (string)cfg_local('email_arquivo_teste', '');
    if ($arquivoTeste !== '') {
        $linhas = 'To: ' . $para . "\r\nSubject: " . $assuntoMime . "\r\n";
        foreach ($cabecalhos as $k => $v) {
            $linhas .= $k . ': ' . $v . "\r\n";
        }
        return file_put_contents($arquivoTeste, $linhas . "\r\n" . $corpo . "\r\n\r\n", FILE_APPEND | LOCK_EX) !== false;
    }
    // -f define o remetente do envelope (Return-Path): ajuda no SPF e devolve erro para o domínio certo.
    return mail($para, $assuntoMime, $corpo, $cabecalhos, '-f' . $remetente);
}
