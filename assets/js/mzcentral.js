/* PLACATA /mzcentral — pequenos ajudantes do painel (sem dependências). */
(function () {
  'use strict';

  // Confirmação antes de ações perigosas (excluir).
  document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirmar'))) e.preventDefault();
    });
  });

  // Máscara do WhatsApp: (62) 99999-9999
  document.querySelectorAll('input[data-mascara-tel]').forEach(function (i) {
    i.addEventListener('input', function () {
      var d = i.value.replace(/\D/g, '');
      if (d.length > 11 && d.indexOf('55') === 0) d = d.slice(2);
      d = d.slice(0, 11);
      var r = d;
      if (d.length > 2) r = '(' + d.slice(0, 2) + ') ' + d.slice(2);
      if (d.length > 7) r = '(' + d.slice(0, 2) + ') ' + d.slice(2, d.length === 11 ? 7 : 6) + '-' + d.slice(d.length === 11 ? 7 : 6);
      i.value = r;
    });
  });

  // Prévia da foto escolhida antes de enviar.
  document.querySelectorAll('input[type=file][data-previa]').forEach(function (i) {
    i.addEventListener('change', function () {
      var alvo = document.getElementById(i.getAttribute('data-previa'));
      var f = i.files && i.files[0];
      if (!alvo || !f) return;
      if (f.size > 5 * 1024 * 1024) {
        window.alert('Esta foto tem mais de 5 MB. Escolha uma foto menor.');
        i.value = '';
        return;
      }
      var img = alvo.querySelector('img') || alvo.appendChild(document.createElement('img'));
      img.alt = 'Prévia da foto';
      img.src = URL.createObjectURL(f);
      alvo.hidden = false;
    });
  });

  // Fundo branco: atualiza a prévia na hora.
  document.querySelectorAll('input[data-fundo-claro]').forEach(function (c) {
    c.addEventListener('change', function () {
      var alvo = document.getElementById(c.getAttribute('data-fundo-claro'));
      if (alvo) alvo.classList.toggle('claro', c.checked);
    });
  });
})();
