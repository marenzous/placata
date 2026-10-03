<?php
/** /mzcentral — textos do topo (título grande e frase de apresentação). */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

exigir_login();
$pdo = db();
$cfg = array_merge(conteudo_padrao()['config'], ler_configuracoes($pdo));
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $cfg['hero_titulo'] = trim((string)($_POST['hero_titulo'] ?? ''));
    $cfg['hero_subtitulo'] = trim((string)($_POST['hero_subtitulo'] ?? ''));
    if (mb_strlen($cfg['hero_titulo']) < 3 || mb_strlen($cfg['hero_titulo']) > 140) {
        $erros[] = 'O título precisa ter entre 3 e 140 letras.';
    }
    if (mb_strlen($cfg['hero_subtitulo']) < 3 || mb_strlen($cfg['hero_subtitulo']) > 400) {
        $erros[] = 'A frase de apresentação precisa ter entre 3 e 400 letras.';
    }
    if (!$erros) {
        salvar_configuracao($pdo, 'hero_titulo', $cfg['hero_titulo']);
        salvar_configuracao($pdo, 'hero_subtitulo', $cfg['hero_subtitulo']);
        avisar('Textos salvos. Já estão no site.');
        redirecionar('/mzcentral/textos.php');
    }
}

admin_inicio('Textos do topo', 'textos');
?>
      <h1>Textos do topo</h1>
      <p class="adm-ajuda">É a primeira coisa que o visitante lê ao abrir o site.</p>
<?php if ($erros): ?>
      <div role="alert" class="alerta erro"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
      <form method="post" class="adm-card adm-form">
        <?= csrf_campo() ?>
        <div class="campo">
          <label for="t-titulo">Título grande</label>
          <input id="t-titulo" type="text" name="hero_titulo" maxlength="140" required value="<?= h($cfg['hero_titulo']) ?>">
        </div>
        <div class="campo">
          <label for="t-sub">Frase de apresentação</label>
          <textarea id="t-sub" name="hero_subtitulo" maxlength="400" required><?= h($cfg['hero_subtitulo']) ?></textarea>
        </div>
        <div class="adm-linha-botoes"><button type="submit" class="adm-btn primario">Salvar</button></div>
      </form>
<?php
admin_fim();
