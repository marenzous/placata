<?php
/**
 * /mzcentral — esqueci minha senha (pede o link por e-mail).
 * Resposta SEMPRE a mesma, exista ou não o e-mail (não revela quem tem acesso).
 */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

admin_cabecalhos();
admin_sessao();

if (admin_logado()) {
    redirecionar('/mzcentral/painel.php');
}

const MSG_GENERICA = 'Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha. Confira a caixa de entrada e também o spam. O link vale por 30 minutos.';

/*
 * Anti-enumeração por TEMPO: todo POST leva o mesmo tempo mínimo (~1,5 s + até
 * 0,25 s aleatório, contado do início da requisição), exista ou não o e-mail,
 * e inclusive quando o limite foi atingido. A resposta genérica é entregue
 * ANTES de criar o link e chamar mail(): com PHP-FPM a conexão é fechada
 * (fastcgi_finish_request); sem FPM (módulo Apache/CGI) vai com Content-Length
 * + Connection: close + flush, e o resto roda depois (ignore_user_abort).
 */
const ESQUECI_TEMPO_MIN_SEG = 1.5;
const ESQUECI_VARIACAO_MS = 250;

/** Cria o link e envia o e-mail. Nunca derruba a resposta: erro só vai pro log. */
function esqueci_enviar_link(PDO $pdo, array $u, string $ip): void
{
    try {
        $token = redefinicao_criar($pdo, (int)$u['id'], $ip);
        $ok = enviar_email(
            (string)$u['email'],
            'Redefinição de senha do painel PLACATA',
            redefinicao_email_corpo(redefinicao_link($token))
        );
        if (!$ok) {
            error_log('PLACATA mzcentral: falha ao enviar e-mail de redefinição de senha (admin id ' . (int)$u['id'] . ').');
        }
    } catch (Throwable $e) {
        error_log('PLACATA mzcentral esqueci (envio): ' . $e->getMessage());
    }
}

$erro = '';
$enviado = false;
$email = '';
$ehPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$pendente = null; // admin que deve receber o link (só depois da resposta)
$pdo = null;
$ip = ip_cliente();

if ($ehPost) {
    csrf_verificar();
    $email = mb_substr(mb_strtolower(trim((string)($_POST['email'] ?? ''))), 0, 190);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } else {
        try {
            $pdo = db();
            redefinicao_garantir_tabelas($pdo);
            if (redefinicao_bloqueada($pdo, $ip, $email)) {
                $erro = 'Muitos pedidos em pouco tempo. Por segurança, aguarde uma hora e tente de novo.';
            } else {
                redefinicao_registrar($pdo, $ip, $email, 'pedido');
                $st = $pdo->prepare('SELECT id, email FROM admins WHERE email = ? AND ativo = 1 LIMIT 1');
                $st->execute([$email]);
                $u = $st->fetch();
                if ($u) {
                    $pendente = $u;
                }
                $enviado = true;
            }
        } catch (Throwable $e) {
            error_log('PLACATA mzcentral esqueci: ' . $e->getMessage());
            admin_erro_banco();
        }
    }
}

ob_start();
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Esqueci minha senha · PLACATA /mzcentral</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= h(PLACATA_ASSET_V) ?>">
  <link rel="stylesheet" href="/assets/css/mzcentral.css?v=<?= h(PLACATA_ASSET_V) ?>">
</head>
<body class="adm">
  <main class="login">
    <div class="adm-card">
      <img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="149" height="30">
      <h1 class="login-titulo">Esqueci minha senha</h1>
<?php if ($enviado): ?>
      <div role="status" class="alerta ok"><?= h(MSG_GENERICA) ?></div>
<?php else: ?>
<?php if ($erro): ?>
      <div role="alert" class="alerta erro"><?= h($erro) ?></div>
<?php endif; ?>
      <p class="login-texto">Digite o e-mail de acesso ao painel. Vamos enviar um link para você criar uma nova senha.</p>
      <form method="post" action="/mzcentral/esqueci.php" autocomplete="on">
        <?= csrf_campo() ?>
        <div class="campo">
          <label for="email">E-mail</label>
          <input id="email" type="email" name="email" value="<?= h($email) ?>" required autocomplete="username" autofocus>
        </div>
        <button type="submit" class="adm-btn primario largo">Enviar link</button>
      </form>
<?php endif; ?>
      <p class="login-rodape"><a href="/mzcentral/">Voltar para o login</a></p>
    </div>
  </main>
</body>
</html>
<?php
$html = (string)ob_get_clean();

if ($ehPost) {
    session_write_close();
    $inicio = (float)($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    $alvo = $inicio + ESQUECI_TEMPO_MIN_SEG + random_int(0, ESQUECI_VARIACAO_MS) / 1000;
    $falta = $alvo - microtime(true);
    if ($falta > 0) {
        usleep((int)round($falta * 1000000));
    }
}

if ($ehPost) {
    // Em TODO POST, nao so quando o e-mail existe: cabecalho diferente entregaria
    // quem esta cadastrado (achado do security-gate na revisao de fdc635d).
    ignore_user_abort(true); // o envio continua mesmo depois que o navegador fecha
    header('Connection: close');
}
header('Content-Length: ' . strlen($html));
echo $html;
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // PHP-FPM: o navegador já recebeu tudo; o resto roda em segundo plano
} else {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

if ($pendente && $pdo) {
    @set_time_limit(30); // o envio nao pode prender o processo indefinidamente
    esqueci_enviar_link($pdo, $pendente, $ip);
}
