<?php

namespace App\Core;

/**
 * Baixa uma imagem de uma URL fornecida por um cliente externo (o Worker
 * MCP) de forma segura: recusa qualquer coisa que nao seja https, recusa
 * hostname que resolva pra IP privado/loopback/reservado (guarda contra
 * SSRF — sem isso, um cliente mal-intencionado podia usar essa rota pra
 * sondar a rede interna do servidor), limita tamanho de download, e
 * confere o mime real dos bytes baixados em vez de confiar na URL.
 */
class RemoteImageFetcher
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }

        $ip = gethostbyname($parts['host']);
        $validPublicIp = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return $validPublicIp !== false;
    }

    /**
     * @return array{path: string, mime: string, size: int}|null
     */
    public static function download(string $url, int $maxBytes): ?array
    {
        if (!self::isSafeUrl($url)) {
            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mcp_img_');
        $tmpFile = fopen($tmpPath, 'wb');

        if ($tmpFile === false) {
            return null;
        }

        $bytesWritten = 0;
        $exceeded = false;

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($tmpFile, &$bytesWritten, &$exceeded, $maxBytes) {
                $bytesWritten += strlen($chunk);

                if ($bytesWritten > $maxBytes) {
                    $exceeded = true;

                    return 0;
                }

                fwrite($tmpFile, $chunk);

                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        fclose($tmpFile);

        if ($ok === false || $exceeded || $httpCode < 200 || $httpCode >= 300) {
            @unlink($tmpPath);

            return null;
        }

        $mime = (string) @mime_content_type($tmpPath);

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            @unlink($tmpPath);

            return null;
        }

        return ['path' => $tmpPath, 'mime' => $mime, 'size' => $bytesWritten];
    }
}
