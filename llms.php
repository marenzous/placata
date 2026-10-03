<?php
/** PLACATA — llms.txt (servido em /llms.txt via .htaccess), telefone vindo do banco. */

declare(strict_types=1);

require __DIR__ . '/config.php';

$c = carregar_conteudo();
$w = $c['config']['whatsapp'];
$tel = formatar_telefone($w);
$area = $c['config']['area_texto'];

header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo "# PLACATA\n\n";
echo "> PLACATA (placata.com.br) fabrica placas para túmulo em alumínio e em vidro (com foto, nome, datas e mensagem, inclusive placas de jazigo de família com várias fotos) e placas de endereço residencial (rua, quadra, lote, setor e CEP, no padrão de Goiânia, em fundo preto com letras douradas ou prata em relevo, e caixas de correspondência em alumínio com o endereço). Atende {$area} e entrega na casa do cliente. {$c['config']['atendimento_texto']} pelo WhatsApp {$tel}.\n\n";
echo "## Páginas\n";
echo "- [Início](" . PLACATA_URL . "/): modelos de placa para túmulo e de endereço, como funciona e dúvidas frequentes\n";
echo "- [Privacidade](" . PLACATA_URL . "/privacidade/)\n\n";
echo "## Como pedir\n";
echo "- Pelo WhatsApp {$tel}: https://wa.me/" . preg_replace('/\D+/', '', $w) . "\n";
