<?php
/** /mzcentral — sair (somente POST com CSRF). */

declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../inc/admin.php';

admin_cabecalhos();
admin_sessao();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    admin_sair();
}
redirecionar('/mzcentral/');
