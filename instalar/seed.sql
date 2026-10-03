-- PLACATA — conteúdo inicial (igual ao site aprovado v1.02).
-- Só roda em banco vazio (o instalar.php confere antes).
SET NAMES utf8mb4;

INSERT INTO configuracoes (chave, valor) VALUES
  ('hero_titulo', 'Placas para túmulo e placas de endereço'),
  ('hero_subtitulo', 'Fabricamos placas pra túmulo de alumínio e também de vidro, e placas de endereço residencial. Entregamos na sua casa.'),
  ('whatsapp', '5562996995138'),
  ('atendimento_texto', 'Atendimento 24 horas'),
  ('area_texto', 'Goiânia e região');

INSERT INTO produtos (categoria, titulo, descricao, foto, foto_alt, foto_largura, foto_altura, fundo_claro, ordem, ativo) VALUES
  ('tumulo', 'Placa de túmulo pergaminho com cruz', 'Alumínio em formato de pergaminho, com cruz, foto oval, nome, datas e mensagem.', 'tumulo-pergaminho-cruz-foto-oval.webp', 'Placa de túmulo em alumínio no formato de pergaminho, com cruz, foto oval, nome, datas e mensagem de saudade', 643, 900, 1, 1, 1),
  ('tumulo', 'Placa de túmulo retangular com foto', 'Retangular preta, com foto, nome, datas e mensagem. Fixação com parafusos.', 'tumulo-retangular-preta-foto-mensagem.webp', 'Placa de túmulo retangular preta com foto, nome, datas e mensagem, fixada em granito', 252, 280, 0, 2, 1),
  ('tumulo', 'Placa de túmulo com foto oval e moldura', 'Foto oval com moldura prateada, nome, datas e frase.', 'tumulo-foto-oval-moldura.webp', 'Placa de túmulo preta com foto oval em moldura prateada, nome, datas e frase', 251, 200, 0, 3, 1),
  ('tumulo', 'Placa de vidro para jazigo de família', 'Placa em vidro com várias fotos, nomes e datas da família.', 'vidro-familia-varias-fotos.webp', 'Placa de vidro para jazigo de família com quatro fotos, nomes e datas sobre fundo de céu', 250, 320, 0, 4, 1),
  ('endereco', 'Placa de endereço com letras douradas', 'Preta com letras e ornamentos dourados: rua, quadra, lote, número, setor e CEP.', 'endereco-rua-das-palmeiras-dourada.webp', 'Placa de endereço residencial preta com letras douradas e ornamentos nos cantos', 251, 359, 1, 1, 1),
  ('endereco', 'Placa de endereço com letras prata', 'Preta com letras prateadas em letra cursiva, no padrão de Goiânia.', 'endereco-rua-terativo-prata.webp', 'Placa de endereço residencial preta com letras prateadas cursivas, rua, quadra, lote, setor e CEP', 250, 235, 1, 2, 1),
  ('endereco', 'Caixa correspondência', 'Com seu endereço. Toda de alumínio.', 'endereco-correspondencias-casinha.webp', 'Caixa de correspondência em alumínio, em formato de casinha, com o endereço em relevo', 252, 165, 1, 3, 1),
  ('endereco', 'Caixa correspondência', 'Com seu endereço. Toda de alumínio.', 'endereco-correspondencias-passaro.webp', 'Caixa de correspondência toda de alumínio, com pássaro e o endereço em relevo', 251, 337, 1, 4, 1),
  ('endereco', 'Placa de endereço em relevo prata', 'Fundo preto com letras prata em relevo: rua, quadra, lote, residencial e CEP.', 'endereco-relevo-prata-solar-ville.webp', 'Placa de endereço residencial preta com letras prata em relevo', 250, 210, 1, 5, 1);

INSERT INTO faq (pergunta, resposta, ordem, ativo) VALUES
  ('Vocês fazem placa para túmulo de alumínio e de vidro?', 'Sim. Fabricamos placas para túmulo em alumínio, com foto, nome, datas e mensagem, e também placas de vidro, inclusive para jazigo de família com várias fotos.', 1, 1),
  ('Posso colocar foto na placa?', 'Pode. Envie a foto pelo WhatsApp, junto com o nome, as datas e a mensagem que deseja. Combinamos todos os detalhes com você antes de produzir.', 2, 1),
  ('Vocês fazem placa de endereço para casa?', 'Sim. Fazemos placas de endereço residencial com rua, quadra, lote, número, setor e CEP, em fundo preto com letras douradas ou prata em relevo, inclusive modelos com a faixa "Correspondências".', 3, 1),
  ('Vocês entregam?', 'Sim, entregamos na sua casa. Atendemos Goiânia e região; para outras cidades, consulte pelo WhatsApp.', 4, 1),
  ('Qual é o horário de atendimento?', 'O atendimento pelo WhatsApp é 24 horas, todos os dias, no número (62) 99699-5138.', 5, 1);
