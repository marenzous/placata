<?php
/** /mzcentral — tela inicial do painel. */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

exigir_login();
admin_inicio('Início', 'painel');
?>
      <h1>Olá! O que você quer mudar no site?</h1>
      <p class="adm-ajuda">Tudo o que você salvar aqui aparece no site na hora.</p>
      <div class="adm-grade adm-espaco">
        <a href="/mzcentral/produtos.php" class="adm-card"><h3>Produtos</h3><p>Fotos, nomes e descrições das placas. Adicionar, esconder, excluir ou mudar a ordem.</p></a>
        <a href="/mzcentral/textos.php" class="adm-card"><h3>Textos do topo</h3><p>O título grande e a frase de apresentação do começo da página.</p></a>
        <a href="/mzcentral/contato.php" class="adm-card"><h3>Contato</h3><p>Número do WhatsApp, texto de atendimento e área atendida.</p></a>
        <a href="/mzcentral/faq.php" class="adm-card"><h3>Perguntas frequentes</h3><p>As dúvidas que aparecem no final da página (também ajudam no Google).</p></a>
        <a href="/mzcentral/senha.php" class="adm-card"><h3>Trocar minha senha</h3><p>Troque a senha de acesso a este painel.</p></a>
      </div>
<?php
admin_fim();
