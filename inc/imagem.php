<?php
/**
 * Upload de foto de produto: valida o tipo REAL do arquivo (finfo), limita a 5MB,
 * redimensiona para no máximo 1200px e salva em WEBP com GD (ou JPG se o
 * servidor não tiver WEBP). O arquivo original nunca é salvo como veio —
 * sempre é re-codificado, o que descarta qualquer conteúdo escondido.
 */

declare(strict_types=1);

const FOTO_LADO_MAX = 1200;
const FOTO_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * Processa $_FILES['campo'].
 * @return array{arquivo:string,largura:int,altura:int}
 * @throws RuntimeException com mensagem amigável em PT-BR
 */
function processar_foto_upload(array $f, string $destinoDir): array
{
    $erro = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('A foto é maior que 5 MB. Escolha uma foto menor.');
    }
    if ($erro !== UPLOAD_ERR_OK || empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Não foi possível receber a foto. Tente de novo.');
    }
    return processar_foto_arquivo((string)$f['tmp_name'], (int)($f['size'] ?? 0), $destinoDir);
}

/** Núcleo testável (sem depender de is_uploaded_file). */
function processar_foto_arquivo(string $caminho, int $tamanho, string $destinoDir): array
{
    if (!extension_loaded('gd')) {
        throw new RuntimeException('O servidor está sem a extensão GD do PHP. Peça ao suporte para ativá-la.');
    }
    $tamanho = $tamanho > 0 ? $tamanho : (int)@filesize($caminho);
    if ($tamanho <= 0) {
        throw new RuntimeException('O arquivo da foto está vazio.');
    }
    if ($tamanho > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('A foto é maior que 5 MB. Escolha uma foto menor.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($caminho);
    if (!isset(FOTO_MIMES[$mime])) {
        throw new RuntimeException('Formato não aceito. Envie uma foto JPG, PNG ou WEBP.');
    }
    $info = @getimagesize($caminho);
    if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40000000) {
        throw new RuntimeException('Esta imagem não pôde ser lida (ou é grande demais). Tente outra foto.');
    }

    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($caminho),
        'image/png' => @imagecreatefrompng($caminho),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($caminho) : false,
    };
    if (!$img) {
        throw new RuntimeException('Esta imagem não pôde ser lida. Tente outra foto.');
    }

    // Foto de celular deitada: corrige pela orientação EXIF.
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($caminho);
        $ori = (int)($exif['Orientation'] ?? 1);
        $ang = [3 => 180, 6 => -90, 8 => 90][$ori] ?? 0;
        if ($ang !== 0) {
            $rot = imagerotate($img, $ang, 0);
            if ($rot) {
                imagedestroy($img);
                $img = $rot;
            }
        }
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $escala = min(1, FOTO_LADO_MAX / max($w, $h));
    $nw = max(1, (int)round($w * $escala));
    $nh = max(1, (int)round($h * $escala));

    $usaWebp = function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);
    $dst = imagecreatetruecolor($nw, $nh);
    if ($usaWebp) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    } else {
        // JPG não tem transparência: fundo branco.
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    }
    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($img);

    if (!is_dir($destinoDir) && !@mkdir($destinoDir, 0755, true)) {
        imagedestroy($dst);
        throw new RuntimeException('A pasta de fotos não existe e não pôde ser criada.');
    }
    $ext = $usaWebp ? 'webp' : 'jpg';
    $nome = 'produto-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $ok = $usaWebp ? imagewebp($dst, $destinoDir . $nome, 82) : imagejpeg($dst, $destinoDir . $nome, 85);
    imagedestroy($dst);
    if (!$ok) {
        throw new RuntimeException('Não foi possível salvar a foto no servidor (permissão da pasta uploads/produtos).');
    }
    @chmod($destinoDir . $nome, 0644);
    return ['arquivo' => $nome, 'largura' => $nw, 'altura' => $nh];
}

/** Apaga uma foto da pasta de produtos (só nomes seguros, nunca fora da pasta). */
function apagar_foto(string $arquivo, string $dir): void
{
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{0,150}\.(webp|jpg|jpeg|png)$/', $arquivo)) {
        return;
    }
    // Fotos do conteúdo padrão ficam sempre (são o "plano B" se o banco cair).
    foreach (conteudo_padrao()['produtos'] as $p) {
        if ($p['foto'] === $arquivo) {
            return;
        }
    }
    $caminho = $dir . $arquivo;
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}
