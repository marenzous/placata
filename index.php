<?php
/**
 * PLACATA — Home. Renderiza do banco; se o banco falhar, usa o conteúdo padrão.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/inc/layout.php';

$c = carregar_conteudo();
$cfg = $c['config'];
$link = link_whatsapp($cfg['whatsapp']);
$tel = formatar_telefone($cfg['whatsapp']);

$grupos = ['tumulo' => [], 'endereco' => []];
foreach ($c['produtos'] as $p) {
    if (isset($grupos[$p['categoria']])) {
        $grupos[$p['categoria']][] = $p;
    }
}

$titulo = 'Placa para Túmulo em Alumínio e Vidro e Placa de Endereço em Goiânia | PLACATA';
$descricao = 'Fabricamos placa para túmulo em alumínio e vidro, com foto, nome e mensagem, e placa de endereço residencial em Goiânia. Entregamos na sua casa. Atendimento 24 horas pelo WhatsApp.';

// ---- Schema.org (LocalBusiness + WebSite + FAQPage) gerado do banco ----
$horario = [
    '@type' => 'OpeningHoursSpecification',
    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
    'opens' => '00:00',
    'closes' => '23:59',
];
$telSchema = telefone_schema($cfg['whatsapp']);
$grafo = [
    [
        '@type' => 'LocalBusiness',
        '@id' => PLACATA_URL . '/#empresa',
        'name' => 'PLACATA',
        'url' => PLACATA_URL . '/',
        'telephone' => $telSchema,
        'logo' => PLACATA_URL . '/assets/logo/placata-logo.png',
        'image' => PLACATA_URL . '/assets/og-image.png',
        'description' => 'Fabricamos placas para túmulo em alumínio e vidro e placas de endereço residencial. Entregamos na sua casa em Goiânia e região.',
        'areaServed' => ['@type' => 'City', 'name' => 'Goiânia'],
        'openingHoursSpecification' => $horario,
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => $telSchema,
            'contactType' => 'customer service',
            'availableLanguage' => 'Portuguese',
            'hoursAvailable' => $horario,
        ],
    ],
    [
        '@type' => 'WebSite',
        '@id' => PLACATA_URL . '/#site',
        'url' => PLACATA_URL . '/',
        'name' => 'PLACATA',
        'inLanguage' => 'pt-BR',
        'publisher' => ['@id' => PLACATA_URL . '/#empresa'],
    ],
];
if ($c['faq']) {
    $grafo[] = [
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn ($f) => [
            '@type' => 'Question',
            'name' => $f['pergunta'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['resposta']],
        ], $c['faq']),
    ];
}
$jsonld = json_encode(
    ['@context' => 'https://schema.org', '@graph' => $grafo],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
);

pagina_inicio([
    'title' => $titulo,
    'description' => $descricao,
    'canonical' => PLACATA_URL . '/',
    'jsonld' => $jsonld,
], $c);

$secoes = [
    'tumulo' => ['id' => 'placas-para-tumulo', 'aria' => 't-tumulo', 'h2' => 'Placas para túmulo', 'p' => 'Alumínio e vidro, com foto, nome, datas e mensagem.'],
    'endereco' => ['id' => 'placas-de-endereco', 'aria' => 't-end', 'h2' => 'Placas de endereço', 'p' => 'Rua, quadra, lote e CEP, no padrão de Goiânia.'],
];
$n = 0;
?>
  <main id="conteudo">
    <section class="hero">
      <div class="wrap"><div class="estreito">
        <h1><?= h($cfg['hero_titulo']) ?></h1>
        <p><?= h($cfg['hero_subtitulo']) ?></p>
        <div class="acoes">
          <a class="btn-whats " href="<?= h($link) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
          <span class="legenda-24h"><?= h($cfg['atendimento_texto']) ?> · <?= h($cfg['area_texto']) ?></span>
        </div>
      </div></div>
    </section>
<?php foreach ($secoes as $cat => $s): if (!$grupos[$cat]) { continue; } ?>

    <section class="grupo" id="<?= $s['id'] ?>" aria-labelledby="<?= $s['aria'] ?>">
      <div class="wrap">
        <div class="grupo-titulo"><h2 id="<?= $s['aria'] ?>"><?= $s['h2'] ?></h2><p><?= $s['p'] ?></p></div>
        <ul class="grade">
<?php foreach ($grupos[$cat] as $p): $n++; ?>
          <li class="produto">
            <div class="produto-foto<?= (int)$p['fundo_claro'] ? ' claro' : '' ?>"><img src="<?= h(url_foto((string)$p['foto'])) ?>" alt="<?= h(trim((string)($p['foto_alt'] ?? '')) !== '' ? $p['foto_alt'] : $p['titulo']) ?>" width="<?= (int)$p['foto_largura'] ?>" height="<?= (int)$p['foto_altura'] ?>"<?= $n > 3 ? ' loading="lazy"' : '' ?> decoding="async"></div>
            <div class="produto-corpo">
              <h3><?= h($p['titulo']) ?></h3>
              <p><?= h($p['descricao']) ?></p>
              <a class="btn-whats cheio" href="<?= h($link) ?>" target="_blank" rel="noopener">Falar com o vendedor</a>
              <span class="legenda-24h"><?= h($cfg['atendimento_texto']) ?></span>
            </div>
          </li>
<?php endforeach; ?>
        </ul>
      </div>
    </section>
<?php endforeach; ?>

    <section class="secao" id="como-funciona" aria-labelledby="t-como">
      <div class="wrap">
        <h2 id="t-como">Como funciona</h2>
        <ol class="passos">
          <li><h3>Chame no WhatsApp</h3><p>Diga qual modelo você quer, ou mande uma foto de referência.</p></li>
          <li><h3>Envie os dados</h3><p>Nome, datas, mensagem e foto, ou o endereço completo da casa. Combinamos tudo antes de produzir.</p></li>
          <li><h3>Receba em casa</h3><p>Fabricamos a placa e entregamos na sua casa.</p></li>
        </ol>
      </div>
    </section>
<?php if ($c['faq']): ?>

    <section class="secao" id="duvidas" aria-labelledby="t-faq">
      <div class="wrap">
        <h2 id="t-faq">Dúvidas frequentes</h2>
        <div class="faq">
<?php foreach ($c['faq'] as $f): ?>
          <details><summary><?= h($f['pergunta']) ?></summary><div class="resposta"><p><?= nl2br(h($f['resposta']), false) ?></p></div></details>
<?php endforeach; ?>
        </div>
      </div>
    </section>
<?php endif; ?>

    <section class="secao">
      <div class="wrap">
        <div class="chamada">
          <div><h2>Peça seu orçamento agora</h2><p><?= h($cfg['atendimento_texto']) ?> pelo WhatsApp <span><?= h($tel) ?></span>.</p></div>
          <a class="btn-whats " href="<?= h($link) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
        </div>
      </div>
    </section>
  </main>
<?php
pagina_fim($c);
