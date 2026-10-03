<?php
/** PLACATA — Política de privacidade (URL /privacidade/). */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/layout.php';

$c = carregar_conteudo();

pagina_inicio([
    'title' => 'Política de Privacidade | PLACATA',
    'description' => 'Como a PLACATA trata as informações de quem pede orçamento pelo WhatsApp.',
    'canonical' => PLACATA_URL . '/privacidade/',
], $c);
?>
  <main id="conteudo">
    <div class="wrap texto">
      <h1>Política de privacidade</h1>
      <p>Este site não tem formulário e não coleta dados pessoais. Também não usa cookies de publicidade nem ferramentas de rastreamento, e as fontes ficam hospedadas no próprio site.</p>
      <h2>Conversas pelo WhatsApp</h2>
      <p>Quando você nos chama no WhatsApp, os dados que compartilhar (nome, fotos, datas, endereço) são usados somente para preparar o orçamento, produzir e entregar a sua placa, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018). Você pode pedir a exclusão das suas informações a qualquer momento pelo mesmo canal.</p>
    </div>
  </main>
<?php
pagina_fim($c);
