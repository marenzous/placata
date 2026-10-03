<?php
/**
 * Conteúdo padrão embutido (igual ao site aprovado v1.02).
 * Usado quando o banco está fora do ar ou ainda não foi instalado —
 * o visitante nunca vê tela de erro. Mesmo conteúdo do instalar/seed.sql.
 */

declare(strict_types=1);

function conteudo_padrao(): array
{
    return [
        'config' => [
            'hero_titulo' => 'Placas para túmulo e placas de endereço',
            'hero_subtitulo' => 'Fabricamos placas pra túmulo de alumínio e também de vidro, e placas de endereço residencial. Entregamos na sua casa.',
            'whatsapp' => '5562996995138',
            'atendimento_texto' => 'Atendimento 24 horas',
            'area_texto' => 'Goiânia e região',
        ],
        'produtos' => [
            ['id' => 1, 'categoria' => 'tumulo', 'titulo' => 'Placa de túmulo pergaminho com cruz', 'descricao' => 'Alumínio em formato de pergaminho, com cruz, foto oval, nome, datas e mensagem.', 'foto' => 'tumulo-pergaminho-cruz-foto-oval.webp', 'foto_alt' => 'Placa de túmulo em alumínio no formato de pergaminho, com cruz, foto oval, nome, datas e mensagem de saudade', 'foto_largura' => 643, 'foto_altura' => 900, 'fundo_claro' => 1, 'ordem' => 1, 'ativo' => 1],
            ['id' => 2, 'categoria' => 'tumulo', 'titulo' => 'Placa de túmulo retangular com foto', 'descricao' => 'Retangular preta, com foto, nome, datas e mensagem. Fixação com parafusos.', 'foto' => 'tumulo-retangular-preta-foto-mensagem.webp', 'foto_alt' => 'Placa de túmulo retangular preta com foto, nome, datas e mensagem, fixada em granito', 'foto_largura' => 252, 'foto_altura' => 280, 'fundo_claro' => 0, 'ordem' => 2, 'ativo' => 1],
            ['id' => 3, 'categoria' => 'tumulo', 'titulo' => 'Placa de túmulo com foto oval e moldura', 'descricao' => 'Foto oval com moldura prateada, nome, datas e frase.', 'foto' => 'tumulo-foto-oval-moldura.webp', 'foto_alt' => 'Placa de túmulo preta com foto oval em moldura prateada, nome, datas e frase', 'foto_largura' => 251, 'foto_altura' => 200, 'fundo_claro' => 0, 'ordem' => 3, 'ativo' => 1],
            ['id' => 4, 'categoria' => 'tumulo', 'titulo' => 'Placa de vidro para jazigo de família', 'descricao' => 'Placa em vidro com várias fotos, nomes e datas da família.', 'foto' => 'vidro-familia-varias-fotos.webp', 'foto_alt' => 'Placa de vidro para jazigo de família com quatro fotos, nomes e datas sobre fundo de céu', 'foto_largura' => 250, 'foto_altura' => 320, 'fundo_claro' => 0, 'ordem' => 4, 'ativo' => 1],
            ['id' => 5, 'categoria' => 'endereco', 'titulo' => 'Placa de endereço com letras douradas', 'descricao' => 'Preta com letras e ornamentos dourados: rua, quadra, lote, número, setor e CEP.', 'foto' => 'endereco-rua-das-palmeiras-dourada.webp', 'foto_alt' => 'Placa de endereço residencial preta com letras douradas e ornamentos nos cantos', 'foto_largura' => 251, 'foto_altura' => 359, 'fundo_claro' => 1, 'ordem' => 1, 'ativo' => 1],
            ['id' => 6, 'categoria' => 'endereco', 'titulo' => 'Placa de endereço com letras prata', 'descricao' => 'Preta com letras prateadas em letra cursiva, no padrão de Goiânia.', 'foto' => 'endereco-rua-terativo-prata.webp', 'foto_alt' => 'Placa de endereço residencial preta com letras prateadas cursivas, rua, quadra, lote, setor e CEP', 'foto_largura' => 250, 'foto_altura' => 235, 'fundo_claro' => 1, 'ordem' => 2, 'ativo' => 1],
            ['id' => 7, 'categoria' => 'endereco', 'titulo' => 'Caixa correspondência', 'descricao' => 'Com seu endereço. Toda de alumínio.', 'foto' => 'endereco-correspondencias-casinha.webp', 'foto_alt' => 'Caixa de correspondência em alumínio, em formato de casinha, com o endereço em relevo', 'foto_largura' => 252, 'foto_altura' => 165, 'fundo_claro' => 1, 'ordem' => 3, 'ativo' => 1],
            ['id' => 8, 'categoria' => 'endereco', 'titulo' => 'Caixa correspondência', 'descricao' => 'Com seu endereço. Toda de alumínio.', 'foto' => 'endereco-correspondencias-passaro.webp', 'foto_alt' => 'Caixa de correspondência toda de alumínio, com pássaro e o endereço em relevo', 'foto_largura' => 251, 'foto_altura' => 337, 'fundo_claro' => 1, 'ordem' => 4, 'ativo' => 1],
            ['id' => 9, 'categoria' => 'endereco', 'titulo' => 'Placa de endereço em relevo prata', 'descricao' => 'Fundo preto com letras prata em relevo: rua, quadra, lote, residencial e CEP.', 'foto' => 'endereco-relevo-prata-solar-ville.webp', 'foto_alt' => 'Placa de endereço residencial preta com letras prata em relevo', 'foto_largura' => 250, 'foto_altura' => 210, 'fundo_claro' => 1, 'ordem' => 5, 'ativo' => 1],
        ],
        'faq' => [
            ['id' => 1, 'pergunta' => 'Vocês fazem placa para túmulo de alumínio e de vidro?', 'resposta' => 'Sim. Fabricamos placas para túmulo em alumínio, com foto, nome, datas e mensagem, e também placas de vidro, inclusive para jazigo de família com várias fotos.', 'ordem' => 1, 'ativo' => 1],
            ['id' => 2, 'pergunta' => 'Posso colocar foto na placa?', 'resposta' => 'Pode. Envie a foto pelo WhatsApp, junto com o nome, as datas e a mensagem que deseja. Combinamos todos os detalhes com você antes de produzir.', 'ordem' => 2, 'ativo' => 1],
            ['id' => 3, 'pergunta' => 'Vocês fazem placa de endereço para casa?', 'resposta' => 'Sim. Fazemos placas de endereço residencial com rua, quadra, lote, número, setor e CEP, em fundo preto com letras douradas ou prata em relevo, inclusive modelos com a faixa "Correspondências".', 'ordem' => 3, 'ativo' => 1],
            ['id' => 4, 'pergunta' => 'Vocês entregam?', 'resposta' => 'Sim, entregamos na sua casa. Atendemos Goiânia e região; para outras cidades, consulte pelo WhatsApp.', 'ordem' => 4, 'ativo' => 1],
            ['id' => 5, 'pergunta' => 'Qual é o horário de atendimento?', 'resposta' => 'O atendimento pelo WhatsApp é 24 horas, todos os dias, no número (62) 99699-5138.', 'ordem' => 5, 'ativo' => 1],
        ],
    ];
}
