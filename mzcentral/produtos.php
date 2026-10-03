<?php
/** /mzcentral — lista de produtos: ordem (setas), mostrar/esconder, excluir. */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';
require __DIR__ . '/../inc/imagem.php';

exigir_login();
$pdo = db();

/** Reescreve a ordem 1..n de uma categoria, já com a lista na ordem desejada. */
function gravar_ordem(PDO $pdo, array $ids): void
{
    $st = $pdo->prepare('UPDATE produtos SET ordem = ? WHERE id = ?');
    foreach (array_values($ids) as $i => $id) {
        $st->execute([$i + 1, (int)$id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = (string)($_POST['acao'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    $st = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) {
        avisar('Produto não encontrado.', 'erro');
        redirecionar('/mzcentral/produtos.php');
    }

    if ($acao === 'subir' || $acao === 'descer') {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT id FROM produtos WHERE categoria = ? ORDER BY ordem, id FOR UPDATE');
        $st->execute([$p['categoria']]);
        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        $pos = array_search($id, $ids, true);
        $alvo = $acao === 'subir' ? $pos - 1 : $pos + 1;
        if ($pos !== false && $alvo >= 0 && $alvo < count($ids)) {
            [$ids[$pos], $ids[$alvo]] = [$ids[$alvo], $ids[$pos]];
        }
        gravar_ordem($pdo, $ids);
        $pdo->commit();
        avisar('Ordem atualizada.');
    } elseif ($acao === 'alternar') {
        $pdo->prepare('UPDATE produtos SET ativo = 1 - ativo WHERE id = ?')->execute([$id]);
        avisar((int)$p['ativo'] ? 'Produto escondido do site.' : 'Produto aparecendo no site.');
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM produtos WHERE id = ?')->execute([$id]);
        apagar_foto((string)$p['foto'], UPLOADS_PRODUTOS_DIR);
        avisar('Produto excluído.');
    }
    redirecionar('/mzcentral/produtos.php#cat-' . $p['categoria']);
}

$todos = $pdo->query("SELECT * FROM produtos ORDER BY FIELD(categoria, 'tumulo', 'endereco'), ordem, id")->fetchAll();
$grupos = ['tumulo' => [], 'endereco' => []];
foreach ($todos as $p) {
    $grupos[$p['categoria']][] = $p;
}

admin_inicio('Produtos', 'produtos');
?>
      <div class="adm-secao-titulo">
        <h1>Produtos</h1>
        <a class="adm-btn primario" href="/mzcentral/produto.php">+ Adicionar produto</a>
      </div>
      <p class="adm-ajuda">Use as setas para mudar a ordem em que aparecem no site. "Esconder" tira do site sem apagar.</p>
<?php foreach ($grupos as $cat => $lista): ?>
      <div class="adm-secao-titulo" id="cat-<?= h($cat) ?>"><h2>Placas de <?= h(mb_strtolower(CATEGORIAS[$cat])) ?></h2></div>
<?php if (!$lista): ?>
      <p class="adm-vazio">Nenhum produto nesta categoria ainda.</p>
<?php else: ?>
      <ul class="adm-lista">
<?php foreach ($lista as $i => $p): ?>
        <li class="adm-item<?= (int)$p['ativo'] ? '' : ' inativo' ?>">
          <div class="thumb<?= (int)$p['fundo_claro'] ? ' claro' : '' ?>"><img src="<?= h(url_foto((string)$p['foto'])) ?>" alt="" loading="lazy"></div>
          <div>
            <h3><?= h($p['titulo']) ?><?= (int)$p['ativo'] ? '' : '<span class="etiqueta">Escondido</span>' ?></h3>
            <p><?= h($p['descricao']) ?></p>
          </div>
          <div class="adm-acoes">
            <form method="post" class="form-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="acao" value="subir"><button class="adm-btn pequeno seta" type="submit" aria-label="Subir <?= h($p['titulo']) ?>" title="Subir"<?= $i === 0 ? ' disabled' : '' ?>>↑</button></form>
            <form method="post" class="form-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="acao" value="descer"><button class="adm-btn pequeno seta" type="submit" aria-label="Descer <?= h($p['titulo']) ?>" title="Descer"<?= $i === count($lista) - 1 ? ' disabled' : '' ?>>↓</button></form>
            <a class="adm-btn pequeno" href="/mzcentral/produto.php?id=<?= (int)$p['id'] ?>">Editar</a>
            <form method="post" class="form-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="acao" value="alternar"><button class="adm-btn pequeno" type="submit"><?= (int)$p['ativo'] ? 'Esconder' : 'Mostrar' ?></button></form>
            <form method="post" class="form-inline" data-confirmar="Excluir &quot;<?= h($p['titulo']) ?>&quot;? Isso não pode ser desfeito."><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="acao" value="excluir"><button class="adm-btn pequeno perigo" type="submit">Excluir</button></form>
          </div>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php endforeach; ?>
<?php
admin_fim();
