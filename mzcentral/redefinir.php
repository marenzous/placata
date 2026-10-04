<?php
/**
 * /mzcentral — criar nova senha a partir do link enviado por e-mail.
 * Link: 30 minutos, uso único. Link errado/vencido conta no limite por IP.
 */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

admin_cabecalhos();
admin_sessao();

$token = (string)($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['token'] ?? '') : ($_GET['token'] ?? ''));
$token = substr(trim($token), 0, 100);
$erros = [];
$estado = 'form'; // form | invalido | bloqueado

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
}

try {
    $pdo = db();
    redefinicao_garantir_tabelas($pdo);
    $ip = ip_cliente();

    if (redefinicao_bloqueada($pdo, $ip, null)) {
        $estado = 'bloqueado';
    } elseif (!redefinicao_buscar($pdo, $token)) {
        redefinicao_registrar($pdo, $ip, '', 'token');
        $estado = 'invalido';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nova = (string)($_POST['senha_nova'] ?? '');
        $conf = (string)($_POST['senha_confirma'] ?? '');
        $erros = senha_nova_erros($nova, $conf);
        if (!$erros) {
            if (redefinicao_concluir($pdo, $token, $nova)) {
                // Encerra qualquer sessão deste navegador; as outras caem pela marca da senha.
                admin_sair();
                admin_sessao();
                session_regenerate_id(true);
                avisar('Senha criada. Entre com o seu e-mail e a nova senha.');
                redirecionar('/mzcentral/');
            }
            $estado = 'invalido';
        }
    }
} catch (Throwable $e) {
    error_log('PLACATA mzcentral redefinir: ' . $e->getMessage());
    admin_erro_banco();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Criar nova senha · PLACATA /mzcentral</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= h(PLACATA_ASSET_V) ?>">
  <link rel="stylesheet" href="/assets/css/mzcentral.css?v=<?= h(PLACATA_ASSET_V) ?>">
</head>
<body class="adm">
  <main class="login">
    <div class="adm-card">
      <img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="149" height="30">
      <h1 class="login-titulo">Criar nova senha</h1>
<?php if ($estado === 'bloqueado'): ?>
      <div role="alert" class="alerta erro">Muitas tentativas em pouco tempo. Por segurança, aguarde uma hora e tente de novo.</div>
<?php elseif ($estado === 'invalido'): ?>
      <div role="alert" class="alerta erro">Este link não vale mais: ele vence em 30 minutos e só pode ser usado uma vez. Peça um novo.</div>
      <a class="adm-btn primario largo" href="/mzcentral/esqueci.php">Pedir um novo link</a>
<?php else: ?>
<?php if ($erros): ?>
      <div role="alert" class="alerta erro"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
      <form method="post" action="/mzcentral/redefinir.php" autocomplete="off">
        <?= csrf_campo() ?>
        <input type="hidden" name="token" value="<?= h($token) ?>">
        <div class="campo">
          <label for="s-nova">Nova senha</label>
          <input id="s-nova" type="password" name="senha_nova" required minlength="<?= SENHA_MINIMO ?>" autocomplete="new-password" autofocus>
          <span class="dica">Pelo menos <?= SENHA_MINIMO ?> caracteres. Uma frase fácil de lembrar é ótima, ex.: "placa-dourada-na-porta".</span>
        </div>
        <div class="campo">
          <label for="s-conf">Repita a nova senha</label>
          <input id="s-conf" type="password" name="senha_confirma" required minlength="<?= SENHA_MINIMO ?>" autocomplete="new-password">
        </div>
        <button type="submit" class="adm-btn primario largo">Salvar nova senha</button>
      </form>
<?php endif; ?>
      <p class="login-rodape"><a href="/mzcentral/">Voltar para o login</a></p>
    </div>
  </main>
</body>
</html>
