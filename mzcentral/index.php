<?php
/** /mzcentral — login (e-mail + senha). */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

admin_cabecalhos();
admin_sessao();

if (admin_logado()) {
    redirecionar('/mzcentral/painel.php');
}

$erro = '';
$email = '';
$semAdmin = false;

try {
    $pdo = db();
    $semAdmin = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0;
} catch (Throwable $e) {
    $pdo = null;
    $erro = 'O painel ainda não está conectado ao banco de dados. Confira o arquivo config.local.php e rode /mzcentral/instalar.php.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    csrf_verificar();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $senha = (string)($_POST['senha'] ?? '');
    $ip = ip_cliente();
    $emailChave = mb_substr($email, 0, 190);

    if (login_bloqueado($pdo, $ip, $emailChave)) {
        $erro = 'Muitas tentativas erradas. Por segurança, aguarde 15 minutos e tente de novo.';
    } else {
        $st = $pdo->prepare('SELECT id, email, nome, senha_hash FROM admins WHERE email = ? AND ativo = 1 LIMIT 1');
        $st->execute([$emailChave]);
        $u = $st->fetch();
        // Mesmo custo de tempo com ou sem usuário (não revela se o e-mail existe).
        $hash = $u['senha_hash'] ?? '$2y$10$tpIPxBexXS7qJxmOti1msu1Y1Dp.eTXHsBw.Re7gIuNNoojIdjZOK';
        if (password_verify($senha, $hash) && $u) {
            login_limpar($pdo, $ip, $emailChave);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$u['id'];
            $_SESSION['admin_email'] = $u['email'];
            $_SESSION['ultimo_uso'] = time();
            $_SESSION['senha_marca'] = admin_senha_marca($u['senha_hash']);
            unset($_SESSION['csrf']);
            if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) {
                $novoHash = password_hash($senha, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE admins SET senha_hash = ? WHERE id = ?')->execute([$novoHash, $u['id']]);
                $_SESSION['senha_marca'] = admin_senha_marca($novoHash);
            }
            $pdo->prepare('UPDATE admins SET ultimo_login = NOW() WHERE id = ?')->execute([$u['id']]);
            redirecionar('/mzcentral/painel.php');
        }
        login_registrar_falha($pdo, $ip, $emailChave);
        $erro = 'E-mail ou senha incorretos.';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Entrar · PLACATA /mzcentral</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= PLACATA_ASSET_V ?>">
  <link rel="stylesheet" href="/assets/css/mzcentral.css?v=<?= PLACATA_ASSET_V ?>">
</head>
<body class="adm">
  <main class="login">
    <div class="adm-card">
      <img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="149" height="30">
      <h1 class="login-titulo">Entrar no painel</h1>
      <?= mostrar_aviso() ?>
<?php if ($erro): ?>
      <div role="alert" class="alerta erro"><?= h($erro) ?></div>
<?php endif; ?>
<?php if ($semAdmin): ?>
      <div class="alerta ok">Nenhum acesso criado ainda. Abra <a href="/mzcentral/instalar.php">/mzcentral/instalar.php</a> para criar o primeiro.</div>
<?php endif; ?>
      <form method="post" action="/mzcentral/" autocomplete="on">
        <?= csrf_campo() ?>
        <div class="campo">
          <label for="email">E-mail</label>
          <input id="email" type="email" name="email" value="<?= h($email) ?>" required autocomplete="username" autofocus>
        </div>
        <div class="campo">
          <label for="senha">Senha</label>
          <input id="senha" type="password" name="senha" required autocomplete="current-password">
        </div>
        <button type="submit" class="adm-btn primario largo">Entrar</button>
      </form>
      <p class="login-rodape"><a href="/mzcentral/esqueci.php">Esqueci minha senha</a></p>
    </div>
  </main>
</body>
</html>
