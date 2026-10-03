<?php
/** /mzcentral — contato: WhatsApp, texto de atendimento e área atendida. */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

exigir_login();
$pdo = db();
$cfg = array_merge(conteudo_padrao()['config'], ler_configuracoes($pdo));
$whatsDigitado = formatar_telefone($cfg['whatsapp']);
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $whatsDigitado = trim((string)($_POST['whatsapp'] ?? ''));
    $numero = normalizar_whatsapp($whatsDigitado);
    $cfg['atendimento_texto'] = trim((string)($_POST['atendimento_texto'] ?? ''));
    $cfg['area_texto'] = trim((string)($_POST['area_texto'] ?? ''));

    if (!whatsapp_valido($numero)) {
        $erros[] = 'Número de WhatsApp inválido. Digite com DDD, por exemplo: (62) 99699-5138.';
    }
    if (mb_strlen($cfg['atendimento_texto']) < 2 || mb_strlen($cfg['atendimento_texto']) > 80) {
        $erros[] = 'O texto de atendimento precisa ter entre 2 e 80 letras.';
    }
    if (mb_strlen($cfg['area_texto']) < 2 || mb_strlen($cfg['area_texto']) > 80) {
        $erros[] = 'A área atendida precisa ter entre 2 e 80 letras.';
    }
    if (!$erros) {
        salvar_configuracao($pdo, 'whatsapp', $numero);
        salvar_configuracao($pdo, 'atendimento_texto', $cfg['atendimento_texto']);
        salvar_configuracao($pdo, 'area_texto', $cfg['area_texto']);
        avisar('Contato salvo. Todos os botões de WhatsApp do site já usam o número ' . formatar_telefone($numero) . '.');
        redirecionar('/mzcentral/contato.php');
    }
}

admin_inicio('Contato', 'contato');
?>
      <h1>Contato</h1>
      <p class="adm-ajuda">O número vale para todos os botões de WhatsApp do site.</p>
<?php if ($erros): ?>
      <div role="alert" class="alerta erro"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
      <form method="post" class="adm-card adm-form">
        <?= csrf_campo() ?>
        <div class="campo">
          <label for="c-whats">WhatsApp (com DDD)</label>
          <input id="c-whats" type="tel" name="whatsapp" inputmode="tel" maxlength="20" required data-mascara-tel value="<?= h($whatsDigitado) ?>" placeholder="(62) 99699-5138">
          <span class="dica">Mensagem que o cliente manda ao tocar no botão: "<?= h(MENSAGEM_WHATSAPP) ?>"</span>
        </div>
        <div class="campo">
          <label for="c-atend">Texto de atendimento</label>
          <input id="c-atend" type="text" name="atendimento_texto" maxlength="80" required value="<?= h($cfg['atendimento_texto']) ?>">
          <span class="dica">Aparece embaixo dos botões. Ex.: "Atendimento 24 horas".</span>
        </div>
        <div class="campo">
          <label for="c-area">Área atendida</label>
          <input id="c-area" type="text" name="area_texto" maxlength="80" required value="<?= h($cfg['area_texto']) ?>">
          <span class="dica">Ex.: "Goiânia e região".</span>
        </div>
        <div class="adm-linha-botoes"><button type="submit" class="adm-btn primario">Salvar</button></div>
      </form>
<?php
admin_fim();
