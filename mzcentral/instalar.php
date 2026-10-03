<?php
/**
 * /mzcentral/instalar.php — instalação única.
 *
 * 1) Cria as tabelas (instalar/schema.sql) se ainda não existirem.
 * 2) Carrega o conteúdo inicial (instalar/seed.sql) se o banco estiver vazio.
 * 3) Cria o PRIMEIRO acesso ao painel — só funciona enquanto não existir
 *    nenhum admin. A senha é digitada no formulário e gravada só como hash.
 * 4) Depois de criar o acesso, tenta apagar a si mesmo. Se não conseguir,
 *    pede para apagar o arquivo pelo Gerenciador de Arquivos do Plesk.
 *    Mesmo que fique no servidor, ele se recusa a rodar de novo.
 */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

admin_cabecalhos();
admin_sessao();

/** Executa um arquivo .sql comando a comando (separa por ";" no fim da linha). */
function executar_sql(PDO $pdo, string $arquivo): void
{
    $sql = (string)file_get_contents($arquivo);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) ?: [] as $cmd) {
        if (trim($cmd) !== '') {
            $pdo->exec($cmd);
        }
    }
}

function tela(string $titulo, string $corpo): void
{
    header('Content-Type: text/html; charset=utf-8');
    $v = PLACATA_ASSET_V;
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow"><title>' . h($titulo) . ' · PLACATA</title>'
        . '<link rel="stylesheet" href="/assets/css/style.css?v=' . $v . '"><link rel="stylesheet" href="/assets/css/mzcentral.css?v=' . $v . '"></head>'
        . '<body class="adm"><main class="login"><div class="adm-card adm-form">'
        . '<img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="149" height="30">'
        . '<h1 class="login-titulo">' . h($titulo) . '</h1>' . $corpo . '</div></main></body></html>';
    exit;
}

// ---- 1. Banco configurado? ----
try {
    $pdo = db();
} catch (Throwable $e) {
    tela('Falta configurar o banco', '<div class="alerta erro">Não consegui conectar ao banco de dados.</div>'
        . '<p>Confira se o arquivo <strong>config.local.php</strong> existe na pasta principal do site (httpdocs) e se o nome do banco, o usuário e a senha estão iguais aos que você criou no Plesk em "Bancos de dados".</p>');
}

if (cfg_local('chave_instalacao', '') === 'TROQUE-POR-UM-CODIGO-SO-SEU') {
    tela('Troque o código de instalação', '<div class="alerta erro">No arquivo config.local.php, troque o valor de <strong>chave_instalacao</strong> por uma palavra só sua e recarregue esta página.</div>');
}

// ---- 2. Já instalado? (existe admin) -> recusa ----
$temTabelaAdmins = (bool)$pdo->query("SHOW TABLES LIKE 'admins'")->fetchColumn();
if ($temTabelaAdmins && (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    http_response_code(403);
    tela('Instalação já concluída', '<div class="alerta ok">O painel já tem acesso criado. Este instalador está desativado.</div>'
        . '<p>Por segurança, apague o arquivo <strong>mzcentral/instalar.php</strong> do servidor (Plesk &gt; Arquivos).</p>'
        . '<p><a class="adm-btn primario" href="/mzcentral/">Ir para o login</a></p>');
}

// ---- 3. Cria tabelas e conteúdo inicial (idempotente) ----
try {
    executar_sql($pdo, __DIR__ . '/../instalar/schema.sql');
    $vazio = (int)$pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn() === 0
        && (int)$pdo->query('SELECT COUNT(*) FROM configuracoes')->fetchColumn() === 0
        && (int)$pdo->query('SELECT COUNT(*) FROM faq')->fetchColumn() === 0;
    if ($vazio) {
        executar_sql($pdo, __DIR__ . '/../instalar/seed.sql');
    }
} catch (Throwable $e) {
    error_log('[placata] instalar: ' . $e->getMessage());
    tela('Erro ao preparar o banco', '<div class="alerta erro">Não foi possível criar as tabelas. Confira se o usuário do banco tem permissão total sobre ele (no Plesk isso já vem assim).</div>');
}

// ---- 4. Formulário do primeiro acesso ----
$email = 'elvilimaramos@gmail.com';
$nome = '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $nome = trim((string)($_POST['nome'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $conf = (string)($_POST['senha_confirma'] ?? '');

    $chave = (string)cfg_local('chave_instalacao', '');
    if ($chave !== '' && !hash_equals($chave, (string)($_POST['chave'] ?? ''))) {
        $erros[] = 'Código de instalação errado (é o que está em config.local.php, campo chave_instalacao).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $erros[] = 'Digite um e-mail válido.';
    }
    if (mb_strlen($nome) > 120) {
        $erros[] = 'O nome pode ter no máximo 120 letras.';
    }
    if (mb_strlen($senha) < 10) {
        $erros[] = 'A senha precisa ter pelo menos 10 caracteres.';
    } elseif ($senha !== $conf) {
        $erros[] = 'A confirmação não é igual à senha.';
    }

    if (!$erros) {
        $pdo->beginTransaction();
        // Trava contra dois envios ao mesmo tempo.
        $ja = (int)$pdo->query('SELECT COUNT(*) FROM admins FOR UPDATE')->fetchColumn();
        if ($ja > 0) {
            $pdo->rollBack();
            tela('Instalação já concluída', '<div class="alerta ok">O acesso já foi criado.</div><p><a class="adm-btn primario" href="/mzcentral/">Ir para o login</a></p>');
        }
        $pdo->prepare('INSERT INTO admins (email, nome, senha_hash) VALUES (?, ?, ?)')
            ->execute([$email, $nome, password_hash($senha, PASSWORD_DEFAULT)]);
        $pdo->commit();

        $apagou = @unlink(__FILE__);
        $msg = $apagou
            ? '<p>O instalador foi apagado automaticamente do servidor.</p>'
            : '<div class="alerta erro">Não consegui apagar o instalador sozinho. Apague o arquivo <strong>mzcentral/instalar.php</strong> pelo Plesk (Arquivos). Ele já está desativado, mas não deve ficar no servidor.</div>';
        tela('Pronto! Acesso criado', '<div class="alerta ok">Acesso criado para ' . h($email) . '.</div>' . $msg
            . '<p><a class="adm-btn primario" href="/mzcentral/">Entrar no painel</a></p>');
    }
}

$corpo = '<p class="adm-ajuda">O banco já está pronto, com os produtos, textos e perguntas do site. Agora crie o acesso ao painel.</p>';
if ($erros) {
    $corpo .= '<div role="alert" class="alerta erro">' . implode('<br>', array_map('h', $erros)) . '</div>';
}
$corpo .= '<form method="post" action="/mzcentral/instalar.php" autocomplete="off">' . csrf_campo()
    . ((string)cfg_local('chave_instalacao', '') !== '' ? '<div class="campo"><label for="i-chave">Código de instalação</label><input id="i-chave" type="password" name="chave" required autocomplete="off"><span class="dica">O mesmo que está em config.local.php (chave_instalacao).</span></div>' : '')
    . '<div class="campo"><label for="i-email">E-mail de acesso</label><input id="i-email" type="email" name="email" required maxlength="190" value="' . h($email) . '" autocomplete="username"></div>'
    . '<div class="campo"><label for="i-nome">Nome (opcional)</label><input id="i-nome" type="text" name="nome" maxlength="120" value="' . h($nome) . '"></div>'
    . '<div class="campo"><label for="i-senha">Senha</label><input id="i-senha" type="password" name="senha" required minlength="10" autocomplete="new-password"><span class="dica">Pelo menos 10 caracteres.</span></div>'
    . '<div class="campo"><label for="i-conf">Repita a senha</label><input id="i-conf" type="password" name="senha_confirma" required minlength="10" autocomplete="new-password"></div>'
    . '<button type="submit" class="adm-btn primario largo">Criar acesso</button></form>';
tela('Instalar o painel', $corpo);
