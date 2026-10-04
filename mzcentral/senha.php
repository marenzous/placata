<?php
/** /mzcentral — trocar a própria senha. */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

exigir_login();
$pdo = db();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $atual = (string)($_POST['senha_atual'] ?? '');
    $nova = (string)($_POST['senha_nova'] ?? '');
    $conf = (string)($_POST['senha_confirma'] ?? '');

    $st = $pdo->prepare('SELECT senha_hash FROM admins WHERE id = ?');
    $st->execute([(int)$_SESSION['admin_id']]);
    $hash = (string)$st->fetchColumn();

    if (!password_verify($atual, $hash)) {
        $erros[] = 'A senha atual está errada.';
    }
    $errosNova = senha_nova_erros($nova, $conf);
    if ($errosNova) {
        $erros = array_merge($erros, $errosNova);
    } elseif ($nova === $atual) {
        $erros[] = 'A nova senha precisa ser diferente da atual.';
    }
    if (!$erros) {
        $novoHash = password_hash($nova, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE admins SET senha_hash = ? WHERE id = ?')->execute([$novoHash, (int)$_SESSION['admin_id']]);
        session_regenerate_id(true);
        // Esta sessão continua; qualquer outra aberta com a senha antiga cai.
        $_SESSION['senha_marca'] = admin_senha_marca($novoHash);
        avisar('Senha trocada. Use a nova senha no próximo acesso.');
        redirecionar('/mzcentral/senha.php');
    }
}

admin_inicio('Trocar minha senha', 'senha');
?>
      <h1>Trocar minha senha</h1>
<?php if ($erros): ?>
      <div role="alert" class="alerta erro"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
      <form method="post" class="adm-card adm-form" autocomplete="off">
        <?= csrf_campo() ?>
        <input type="text" name="usuario" value="<?= h($_SESSION['admin_email'] ?? '') ?>" autocomplete="username" hidden>
        <div class="campo">
          <label for="s-atual">Senha atual</label>
          <input id="s-atual" type="password" name="senha_atual" required autocomplete="current-password">
        </div>
        <div class="campo">
          <label for="s-nova">Nova senha</label>
          <input id="s-nova" type="password" name="senha_nova" required minlength="10" autocomplete="new-password">
          <span class="dica">Pelo menos 10 caracteres. Uma frase fácil de lembrar é ótima, ex.: "placa-dourada-na-porta".</span>
        </div>
        <div class="campo">
          <label for="s-conf">Repita a nova senha</label>
          <input id="s-conf" type="password" name="senha_confirma" required minlength="10" autocomplete="new-password">
        </div>
        <div class="adm-linha-botoes"><button type="submit" class="adm-btn primario">Trocar senha</button></div>
      </form>
<?php
admin_fim();
