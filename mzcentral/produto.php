<?php
/** /mzcentral — adicionar / editar produto (com upload de foto). */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';
require __DIR__ . '/../inc/imagem.php';

exigir_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$p = [
    'categoria' => 'tumulo', 'titulo' => '', 'descricao' => '', 'foto' => '', 'foto_alt' => '',
    'foto_largura' => 800, 'foto_altura' => 800, 'fundo_claro' => 1, 'ativo' => 1,
];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $st->execute([$id]);
    $achado = $st->fetch();
    if (!$achado) {
        avisar('Produto não encontrado.', 'erro');
        redirecionar('/mzcentral/produtos.php');
    }
    $p = $achado;
}

$erros = [];
// O PHP descarta o POST inteiro (até o token) se passar do post_max_size.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $erros[] = 'O envio foi grande demais. A foto precisa ter no máximo 5 MB.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $p['categoria'] = isset(CATEGORIAS[$_POST['categoria'] ?? '']) ? $_POST['categoria'] : '';
    $p['titulo'] = trim((string)($_POST['titulo'] ?? ''));
    $p['descricao'] = trim((string)($_POST['descricao'] ?? ''));
    $p['foto_alt'] = trim((string)($_POST['foto_alt'] ?? ''));
    $p['fundo_claro'] = isset($_POST['fundo_claro']) ? 1 : 0;
    $p['ativo'] = isset($_POST['ativo']) ? 1 : 0;

    if ($p['categoria'] === '') {
        $erros[] = 'Escolha a categoria (Túmulo ou Endereço).';
    }
    if (mb_strlen($p['titulo']) < 3 || mb_strlen($p['titulo']) > 120) {
        $erros[] = 'O nome do produto precisa ter entre 3 e 120 letras.';
    }
    if (mb_strlen($p['descricao']) > 400) {
        $erros[] = 'A descrição pode ter no máximo 400 letras.';
    }
    if (mb_strlen($p['foto_alt']) > 300) {
        $erros[] = 'A descrição da foto pode ter no máximo 300 letras.';
    }

    $temArquivo = isset($_FILES['foto']) && (int)($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!$temArquivo && $p['foto'] === '') {
        $erros[] = 'Escolha a foto do produto.';
    }

    $nova = null;
    if (!$erros && $temArquivo) {
        try {
            $nova = processar_foto_upload($_FILES['foto'], UPLOADS_PRODUTOS_DIR);
        } catch (RuntimeException $e) {
            $erros[] = $e->getMessage();
        }
    }

    if (!$erros) {
        $fotoAntiga = (string)$p['foto'];
        if ($nova) {
            $p['foto'] = $nova['arquivo'];
            $p['foto_largura'] = $nova['largura'];
            $p['foto_altura'] = $nova['altura'];
        }
        $alt = $p['foto_alt'] !== '' ? $p['foto_alt'] : null;
        if ($id) {
            // Se mudou de categoria, vai para o fim da nova lista.
            $st = $pdo->prepare('SELECT categoria FROM produtos WHERE id = ?');
            $st->execute([$id]);
            $catAntiga = (string)$st->fetchColumn();
            $ordemSql = '';
            $params = [$p['categoria'], $p['titulo'], $p['descricao'], $p['foto'], $alt, (int)$p['foto_largura'], (int)$p['foto_altura'], $p['fundo_claro'], $p['ativo']];
            if ($catAntiga !== $p['categoria']) {
                $st = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM produtos WHERE categoria = ?');
                $st->execute([$p['categoria']]);
                $ordemSql = ', ordem = ?';
                $params[] = (int)$st->fetchColumn();
            }
            $params[] = $id;
            $pdo->prepare('UPDATE produtos SET categoria = ?, titulo = ?, descricao = ?, foto = ?, foto_alt = ?, foto_largura = ?, foto_altura = ?, fundo_claro = ?, ativo = ?' . $ordemSql . ' WHERE id = ?')
                ->execute($params);
            if ($nova && $fotoAntiga !== '' && $fotoAntiga !== $nova['arquivo']) {
                apagar_foto($fotoAntiga, UPLOADS_PRODUTOS_DIR);
            }
            avisar('Produto salvo.');
        } else {
            $st = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM produtos WHERE categoria = ?');
            $st->execute([$p['categoria']]);
            $ordem = (int)$st->fetchColumn();
            $pdo->prepare('INSERT INTO produtos (categoria, titulo, descricao, foto, foto_alt, foto_largura, foto_altura, fundo_claro, ordem, ativo) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$p['categoria'], $p['titulo'], $p['descricao'], $p['foto'], $alt, (int)$p['foto_largura'], (int)$p['foto_altura'], $p['fundo_claro'], $ordem, $p['ativo']]);
            avisar('Produto adicionado. Ele entra no fim da lista — use as setas para mudar a posição.');
        }
        redirecionar('/mzcentral/produtos.php#cat-' . $p['categoria']);
    }
}

admin_inicio($id ? 'Editar produto' : 'Adicionar produto', 'produtos');
?>
      <p><a href="/mzcentral/produtos.php">← Voltar para a lista</a></p>
      <h1><?= $id ? 'Editar produto' : 'Adicionar produto' ?></h1>
<?php if ($erros): ?>
      <div role="alert" class="alerta erro"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="adm-card adm-form" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_UPLOAD_BYTES ?>">
        <div class="campo">
          <label for="p-categoria">Categoria</label>
          <select id="p-categoria" name="categoria">
<?php foreach (CATEGORIAS as $valor => $rotulo): ?>
            <option value="<?= h($valor) ?>"<?= $p['categoria'] === $valor ? ' selected' : '' ?>><?= h($rotulo) ?></option>
<?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="p-titulo">Nome do produto</label>
          <input id="p-titulo" type="text" name="titulo" maxlength="120" required value="<?= h($p['titulo']) ?>">
          <span class="dica">O botão "Falar com o vendedor" manda sempre a mesma mensagem: "<?= h(MENSAGEM_WHATSAPP) ?>"</span>
        </div>
        <div class="campo">
          <label for="p-descricao">Descrição curta</label>
          <textarea id="p-descricao" name="descricao" maxlength="400"><?= h($p['descricao']) ?></textarea>
        </div>
        <div class="campo">
          <label for="p-foto">Foto</label>
          <div id="previa" class="foto-previa<?= (int)$p['fundo_claro'] ? ' claro' : '' ?>"<?= $p['foto'] === '' ? ' hidden' : '' ?>>
<?php if ($p['foto'] !== ''): ?>
            <img src="<?= h(url_foto((string)$p['foto'])) ?>" alt="Foto atual">
<?php endif; ?>
          </div>
          <input id="p-foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" data-previa="previa">
          <span class="dica">JPG, PNG ou WEBP, até 5 MB. Pode mandar a foto do celular: o painel diminui e otimiza sozinho.<?= $p['foto'] !== '' ? ' Deixe vazio para manter a foto atual.' : '' ?></span>
        </div>
        <label class="checagem"><input type="checkbox" name="fundo_claro" value="1" data-fundo-claro="previa"<?= (int)$p['fundo_claro'] ? ' checked' : '' ?>> Fundo branco atrás da foto</label>
        <div class="campo">
          <label for="p-alt">Descrição da foto para o Google (opcional)</label>
          <input id="p-alt" type="text" name="foto_alt" maxlength="300" value="<?= h($p['foto_alt'] ?? '') ?>">
          <span class="dica">Ex.: "Placa de túmulo em alumínio com foto oval e cruz". Se ficar vazio, usamos o nome do produto.</span>
        </div>
        <label class="checagem"><input type="checkbox" name="ativo" value="1"<?= (int)$p['ativo'] ? ' checked' : '' ?>> Mostrar no site</label>
        <div class="adm-linha-botoes">
          <button type="submit" class="adm-btn primario"><?= $id ? 'Salvar alterações' : 'Adicionar produto' ?></button>
          <a class="adm-btn" href="/mzcentral/produtos.php">Cancelar</a>
        </div>
      </form>
<?php
admin_fim();
