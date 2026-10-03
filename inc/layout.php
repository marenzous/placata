<?php
/**
 * Cabeçalho e rodapé do site público (mesmo HTML do estático v1.02).
 */

declare(strict_types=1);

/**
 * @param array $m title, description, canonical (ou null), robots, og_url, jsonld (string|null)
 */
function pagina_inicio(array $m, array $c): void
{
    $cfg = $c['config'];
    $tel = formatar_telefone($cfg['whatsapp']);
    $link = link_whatsapp($cfg['whatsapp']);
    $v = PLACATA_ASSET_V;
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($m['title']) ?></title>
  <meta name="description" content="<?= h($m['description']) ?>">
<?php if (!empty($m['canonical'])): ?>
  <link rel="canonical" href="<?= h($m['canonical']) ?>">
<?php endif; ?>
  <meta name="robots" content="<?= h($m['robots'] ?? 'index, follow, max-image-preview:large') ?>">
  <meta name="theme-color" content="#1F1F1F">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="PLACATA">
  <meta property="og:title" content="<?= h($m['title']) ?>">
  <meta property="og:description" content="<?= h($m['description']) ?>">
  <meta property="og:url" content="<?= h($m['og_url'] ?? ($m['canonical'] ?? PLACATA_URL . '/')) ?>">
  <meta property="og:image" content="<?= PLACATA_URL ?>/assets/og-image.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="/favicon-32.png" sizes="32x32" type="image/png">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preload" href="/assets/fonts/manrope-700.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= $v ?>">
<?php if (!empty($m['jsonld'])): ?>
  <script type="application/ld+json">
<?= $m['jsonld'] /* gerado com json_encode + JSON_HEX_TAG (seguro dentro de <script>) */ ?>

  </script>
<?php endif; ?>
</head>
<body>
  <a class="pular" href="#conteudo">Pular para o conteúdo</a>
  <header class="topo">
    <div class="wrap">
      <a class="topo-logo" href="/" aria-label="PLACATA — página inicial"><img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="169" height="34"></a>
      <div class="topo-contato">
        <a class="btn-whats pequeno" href="<?= h($link) ?>" target="_blank" rel="noopener" aria-label="WhatsApp <?= h($tel) ?>"><span><?= h($tel) ?></span></a>
        <span class="legenda-24h"><?= h($cfg['atendimento_texto']) ?></span>
      </div>
    </div>
  </header>
<?php
}

function pagina_fim(array $c): void
{
    $cfg = $c['config'];
    $tel = formatar_telefone($cfg['whatsapp']);
    $link = link_whatsapp($cfg['whatsapp']);
    ?>
  <footer class="rodape">
    <div class="wrap">
      <div class="rodape-topo">
        <div>
          <a class="rodape-logo" href="/" aria-label="PLACATA — página inicial"><img src="/assets/logo/placata-logo-branco.svg" alt="PLACATA" width="169" height="34" loading="lazy"></a>
          <p>Placas para túmulo em alumínio e vidro e placas de endereço.<br><?= h($cfg['area_texto']) ?> · entregamos na sua casa.</p>
        </div>
        <ul>
          <li>WhatsApp: <a href="<?= h($link) ?>" target="_blank" rel="noopener"><span><?= h($tel) ?></span></a></li>
          <li><?= h($cfg['atendimento_texto']) ?></li>
          <li><a href="/privacidade/">Privacidade</a></li>
        </ul>
      </div>
      <div class="rodape-base">
        <span>© <?= date('Y') ?> PLACATA · placata.com.br</span>
        <span class="versao"><?= h(PLACATA_VERSAO) ?></span>
      </div>
    </div>
  </footer>
  <a class="flutuante" href="<?= h($link) ?>" target="_blank" rel="noopener" aria-label="Falar no WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 2s.8 2.3.9 2.5c.1.2 1.6 2.5 4 3.5 1.5.6 2.1.7 2.8.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg></a>
</body>
</html>
<?php
}
