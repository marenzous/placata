<?php
/**
 * Regras do WhatsApp — único lugar que monta link wa.me e formata o número.
 * Mesmas regras do MZ4CMS (src/lib/whatsapp.ts).
 */

declare(strict_types=1);

/** Remove tudo que não é dígito; se digitou só DDD + número, acrescenta o 55. */
function normalizar_whatsapp(string $entrada): string
{
    $d = preg_replace('/\D+/', '', $entrada) ?? '';
    if (strlen($d) === 10 || strlen($d) === 11) {
        $d = '55' . $d;
    }
    return $d;
}

/** Válido = 55 + DDD (11 a 99) + número de 8 ou 9 dígitos. */
function whatsapp_valido(string $digitos): bool
{
    return (bool)preg_match('/^55[1-9][1-9][0-9]{8,9}$/', $digitos);
}

/** '5562996995138' -> '(62) 99699-5138' */
function formatar_telefone(string $digitos): string
{
    $d = preg_replace('/^55/', '', preg_replace('/\D+/', '', $digitos) ?? '') ?? '';
    if (strlen($d) === 11) {
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
    }
    if (strlen($d) === 10) {
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
    }
    return $d;
}

/** '5562996995138' -> '+55-62-99699-5138' (formato do schema.org) */
function telefone_schema(string $digitos): string
{
    $d = preg_replace('/^55/', '', preg_replace('/\D+/', '', $digitos) ?? '') ?? '';
    $meio = strlen($d) === 11 ? substr($d, 2, 5) . '-' . substr($d, 7) : substr($d, 2, 4) . '-' . substr($d, 6);
    return '+55-' . substr($d, 0, 2) . '-' . $meio;
}

/** Link do WhatsApp com a mensagem única do site. */
function link_whatsapp(string $digitos): string
{
    return 'https://wa.me/' . (preg_replace('/\D+/', '', $digitos) ?? '') . '?text=' . rawurlencode(MENSAGEM_WHATSAPP);
}
