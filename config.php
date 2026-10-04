<?php
/**
 * PLACATA — bootstrap do site (PHP + MySQL).
 *
 * Este arquivo NÃO tem segredo nenhum. Os dados do banco ficam em
 * config.local.php (fora do Git — copie de config.local.example.php).
 * Se config.local.php não existir ou o banco cair, o site público
 * continua no ar com o conteúdo padrão embutido (inc/padrao.php).
 */

declare(strict_types=1);

const PLACATA_VERSAO = 'v2.01';
const PLACATA_ASSET_V = '2.01';
const PLACATA_URL = 'https://placata.com.br';
const MENSAGEM_WHATSAPP = 'Olá! Vim pelo site da Placata e gostaria de fazer um orçamento.';

define('PLACATA_RAIZ', __DIR__);
define('UPLOADS_PRODUTOS_DIR', __DIR__ . '/uploads/produtos/');
define('UPLOADS_PRODUTOS_URL', '/uploads/produtos/');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

$PLACATA_CFG = [];
if (is_file(__DIR__ . '/config.local.php')) {
    $PLACATA_CFG = require __DIR__ . '/config.local.php';
    if (!is_array($PLACATA_CFG)) {
        $PLACATA_CFG = [];
    }
}

/** Valor de configuração local (config.local.php). */
function cfg_local(string $chave, $padrao = null)
{
    global $PLACATA_CFG;
    return $PLACATA_CFG[$chave] ?? $padrao;
}

/** Ambiente de desenvolvimento mostra erros; produção nunca. */
if (cfg_local('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

/**
 * Conexão PDO (lazy). Lança exceção se não configurado ou fora do ar —
 * quem chama decide o que fazer (site público cai no conteúdo padrão).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = (string)cfg_local('db_host', '');
    $nome = (string)cfg_local('db_nome', '');
    $user = (string)cfg_local('db_usuario', '');
    $pass = (string)cfg_local('db_senha', '');
    $porta = (int)cfg_local('db_porta', 3306);
    if ($host === '' || $nome === '' || $user === '') {
        throw new RuntimeException('Banco de dados não configurado (config.local.php).');
    }
    $dsn = 'mysql:host=' . $host . ';port=' . $porta . ';dbname=' . $nome . ';charset=utf8mb4';
    $sock = (string)cfg_local('db_socket', '');
    if ($sock !== '') {
        $dsn = 'mysql:unix_socket=' . $sock . ';dbname=' . $nome . ';charset=utf8mb4';
    }
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
    return $pdo;
}

/** Escapa saída HTML. Use em TUDO que vem do banco. */
function h($s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

require_once __DIR__ . '/inc/whatsapp.php';
require_once __DIR__ . '/inc/padrao.php';
require_once __DIR__ . '/inc/conteudo.php';
