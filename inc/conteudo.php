<?php
/**
 * Leitura do conteúdo do site (banco -> fallback no padrão embutido).
 */

declare(strict_types=1);

const CATEGORIAS = [
    'tumulo' => 'Túmulo',
    'endereco' => 'Endereço',
];

/** Lê configurações (chave/valor) do banco. */
function ler_configuracoes(PDO $pdo): array
{
    $cfg = [];
    foreach ($pdo->query('SELECT chave, valor FROM configuracoes')->fetchAll() as $linha) {
        $cfg[$linha['chave']] = (string)$linha['valor'];
    }
    return $cfg;
}

/** Grava (insere ou atualiza) uma configuração. */
function salvar_configuracao(PDO $pdo, string $chave, string $valor): void
{
    $st = $pdo->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    $st->execute([$chave, $valor]);
}

/**
 * Conteúdo que o site público mostra. Nunca lança exceção:
 * qualquer falha de banco devolve o conteúdo padrão embutido.
 */
function carregar_conteudo(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $padrao = conteudo_padrao();
    try {
        $pdo = db();
        $config = array_merge($padrao['config'], array_filter(ler_configuracoes($pdo), static fn ($v) => $v !== ''));
        $produtos = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY FIELD(categoria, 'tumulo', 'endereco'), ordem, id")->fetchAll();
        $faq = $pdo->query('SELECT * FROM faq WHERE ativo = 1 ORDER BY ordem, id')->fetchAll();
        if (!whatsapp_valido((string)$config['whatsapp'])) {
            $config['whatsapp'] = $padrao['config']['whatsapp'];
        }
        $cache = ['config' => $config, 'produtos' => $produtos, 'faq' => $faq, 'origem' => 'banco'];
    } catch (Throwable $e) {
        error_log('[placata] banco indisponível, usando conteúdo padrão: ' . $e->getMessage());
        $cache = $padrao + ['origem' => 'padrao'];
    }
    return $cache;
}

/** URL pública da foto de um produto (só nomes de arquivo seguros). */
function url_foto(string $arquivo): string
{
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{0,150}\.(webp|jpg|jpeg|png)$/', $arquivo)) {
        return '/assets/og-image.png';
    }
    return UPLOADS_PRODUTOS_URL . $arquivo;
}
