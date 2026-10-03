<?php
/**
 * PLACATA — dados do banco de dados.
 *
 * 1) Copie este arquivo para "config.local.php" (mesma pasta).
 * 2) Preencha com os dados do banco que você criou no Plesk
 *    (Sites e Domínios > Bancos de dados).
 * 3) NUNCA envie config.local.php para o GitHub nem por e-mail/WhatsApp.
 */
return [
    'db_host'    => 'localhost',      // no Plesk quase sempre é "localhost"
    'db_porta'   => 3306,
    'db_nome'    => 'placata_site',   // nome do banco criado no Plesk
    'db_usuario' => 'placata_user',   // usuário do banco criado no Plesk
    'db_senha'   => 'TROQUE-PELA-SENHA-DO-BANCO',

    // Código pedido pelo instalador (mzcentral/instalar.php) antes de criar o
    // primeiro acesso. Impede que um estranho crie o acesso antes de você.
    // Troque por uma palavra qualquer só sua. Depois da instalação não é mais usado.
    'chave_instalacao' => 'TROQUE-POR-UM-CODIGO-SO-SEU',

    // Deixe false em produção (true mostra erros técnicos na tela).
    'debug'      => false,

    // Cookie de login só trafega em HTTPS. Deixe true em produção.
    // (Use false apenas para testar no computador sem HTTPS.)
    'cookie_seguro' => true,
];
