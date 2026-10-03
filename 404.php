<?php
/** PLACATA — página 404. */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/inc/layout.php';

http_response_code(404);
$c = carregar_conteudo();

pagina_inicio([
    'title' => 'Página não encontrada | PLACATA',
    'description' => 'Esta página não existe.',
    'canonical' => null,
    'robots' => 'noindex, follow',
    'og_url' => PLACATA_URL . '/404',
], $c);
?>
  <main id="conteudo">
    <div class="wrap texto">
      <h1>Página não encontrada</h1>
      <p>O endereço pode ter mudado. Volte para a página inicial para ver os modelos de placa.</p>
      <p><a href="/">Voltar ao início</a></p>
    </div>
  </main>
<?php
pagina_fim($c);
