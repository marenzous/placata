<?php
/** /mzcentral — perguntas frequentes: adicionar, editar, ordem, mostrar/esconder, excluir. */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

exigir_login();
$pdo = db();

$editarId = (int)($_GET['editar'] ?? 0);
$form = ['id' => 0, 'pergunta' => '', 'resposta' => '', 'ativo' => 1];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = (string)($_POST['acao'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'salvar') {
        $form = [
            'id' => $id,
            'pergunta' => trim((string)($_POST['pergunta'] ?? '')),
            'resposta' => trim((string)($_POST['resposta'] ?? '')),
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
        if (mb_strlen($form['pergunta']) < 5 || mb_strlen($form['pergunta']) > 300) {
            $erros[] = 'A pergunta precisa ter entre 5 e 300 letras.';
        }
        if (mb_strlen($form['resposta']) < 2 || mb_strlen($form['resposta']) > 2000) {
            $erros[] = 'A resposta precisa ter entre 2 e 2000 letras.';
        }
        if (!$erros) {
            if ($id) {
                $pdo->prepare('UPDATE faq SET pergunta = ?, resposta = ?, ativo = ? WHERE id = ?')
                    ->execute([$form['pergunta'], $form['resposta'], $form['ativo'], $id]);
                avisar('Pergunta salva.');
            } else {
                $ordem = (int)$pdo->query('SELECT COALESCE(MAX(ordem), 0) + 1 FROM faq')->fetchColumn();
                $pdo->prepare('INSERT INTO faq (pergunta, resposta, ordem, ativo) VALUES (?, ?, ?, ?)')
                    ->execute([$form['pergunta'], $form['resposta'], $ordem, $form['ativo']]);
                avisar('Pergunta adicionada no fim da lista.');
            }
            redirecionar('/mzcentral/faq.php');
        }
        $editarId = $id;
    } elseif ($acao === 'subir' || $acao === 'descer') {
        $pdo->beginTransaction();
        $ids = array_map('intval', $pdo->query('SELECT id FROM faq ORDER BY ordem, id FOR UPDATE')->fetchAll(PDO::FETCH_COLUMN));
        $pos = array_search($id, $ids, true);
        $alvo = $acao === 'subir' ? $pos - 1 : $pos + 1;
        if ($pos !== false && $alvo >= 0 && $alvo < count($ids)) {
            [$ids[$pos], $ids[$alvo]] = [$ids[$alvo], $ids[$pos]];
        }
        $st = $pdo->prepare('UPDATE faq SET ordem = ? WHERE id = ?');
        foreach ($ids as $i => $fid) {
            $st->execute([$i + 1, $fid]);
        }
        $pdo->commit();
        avisar('Ordem atualizada.');
        redirecionar('/mzcentral/faq.php');
    } elseif ($acao === 'alternar') {
        $pdo->prepare('UPDATE faq SET ativo = 1 - ativo WHERE id = ?')->execute([$id]);
        avisar('Pergunta atualizada.');
        redirecionar('/mzcentral/faq.php');
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM faq WHERE id = ?')->execute([$id]);
        avisar('Pergunta excluída.');
        redirecionar('/mzcentral/faq.php');
    } else {
        redirecionar('/mzcentral/faq.php');
    }
}

if ($editarId && !$erros) {
    $st = $pdo->prepare('SELECT id, pergunta, resposta, ativo FROM faq WHERE id = ?');
    $st->execute([$editarId]);
    $form = $st->fetch() ?: $form;
}

$lista = $pdo->query('SELECT * FROM faq ORDER BY ordem, id')->fetchAll();

admin_inicio('Perguntas frequentes', 'faq');
?>
      <h1>Perguntas frequentes</h1>
      <p class="adm-ajuda">Aparecem no final da página e também são enviadas ao Google como "Perguntas e respostas".</p>
<?php if ($erros): ?>
      <div role="alert" class="alerta erro"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
      <form method="post" class="adm-card adm-form" id="formulario">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
        <h2 class="login-titulo"><?= $form['id'] ? 'Editar pergunta' : 'Nova pergunta' ?></h2>
        <div class="campo">
          <label for="f-perg">Pergunta</label>
          <input id="f-perg" type="text" name="pergunta" maxlength="300" required value="<?= h($form['pergunta']) ?>">
        </div>
        <div class="campo">
          <label for="f-resp">Resposta</label>
          <textarea id="f-resp" name="resposta" maxlength="2000" required><?= h($form['resposta']) ?></textarea>
        </div>
        <label class="checagem"><input type="checkbox" name="ativo" value="1"<?= (int)$form['ativo'] ? ' checked' : '' ?>> Mostrar no site</label>
        <div class="adm-linha-botoes">
          <button type="submit" class="adm-btn primario"><?= $form['id'] ? 'Salvar alterações' : 'Adicionar pergunta' ?></button>
<?php if ($form['id']): ?>
          <a class="adm-btn" href="/mzcentral/faq.php">Cancelar</a>
<?php endif; ?>
        </div>
      </form>

      <div class="adm-secao-titulo"><h2>Perguntas cadastradas</h2></div>
<?php if (!$lista): ?>
      <p class="adm-vazio">Nenhuma pergunta ainda.</p>
<?php else: ?>
      <ul class="adm-lista">
<?php foreach ($lista as $i => $f): ?>
        <li class="adm-item adm-item-faq<?= (int)$f['ativo'] ? '' : ' inativo' ?>">
          <div>
            <h3><?= h($f['pergunta']) ?><?= (int)$f['ativo'] ? '' : '<span class="etiqueta">Escondida</span>' ?></h3>
            <p class="adm-faq-resposta"><?= h($f['resposta']) ?></p>
          </div>
          <div class="adm-acoes">
            <form method="post" class="form-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="acao" value="subir"><button class="adm-btn pequeno seta" type="submit" title="Subir" aria-label="Subir pergunta"<?= $i === 0 ? ' disabled' : '' ?>>↑</button></form>
            <form method="post" class="form-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="acao" value="descer"><button class="adm-btn pequeno seta" type="submit" title="Descer" aria-label="Descer pergunta"<?= $i === count($lista) - 1 ? ' disabled' : '' ?>>↓</button></form>
            <a class="adm-btn pequeno" href="/mzcentral/faq.php?editar=<?= (int)$f['id'] ?>#formulario">Editar</a>
            <form method="post" class="form-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="acao" value="alternar"><button class="adm-btn pequeno" type="submit"><?= (int)$f['ativo'] ? 'Esconder' : 'Mostrar' ?></button></form>
            <form method="post" class="form-inline" data-confirmar="Excluir esta pergunta? Isso não pode ser desfeito."><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="acao" value="excluir"><button class="adm-btn pequeno perigo" type="submit">Excluir</button></form>
          </div>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php
admin_fim();
