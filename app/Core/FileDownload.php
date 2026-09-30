<?php

namespace App\Core;

class FileDownload
{
    public static function stream(string $absolutePath, string $mimeType, string $originalName): void
    {
        // Content-Disposition manda dois nomes: `filename` (ASCII puro, pro
        // navegador que nao entende o formato estendido) e `filename*`
        // (RFC 5987/6266, UTF-8 percent-encoded) — sem o segundo, um nome
        // com acento (comum em nomes de contrato/nota fiscal em pt-BR)
        // chegava truncado/mutilado no `filename` puro em vez de correto.
        $safeName = str_replace(['"', "\r", "\n"], '', $originalName);
        $asciiName = (string) iconv('UTF-8', 'ASCII//TRANSLIT', $safeName);
        $asciiName = $asciiName !== '' ? $asciiName : 'arquivo';

        header('Content-Type: ' . $mimeType);
        header(
            'Content-Disposition: attachment; filename="' . $asciiName . '"; '
            . "filename*=UTF-8''" . rawurlencode($safeName)
        );
        header('Content-Length: ' . (string) filesize($absolutePath));
        readfile($absolutePath);
    }
}
