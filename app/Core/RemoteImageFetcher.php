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
        return self::resolveSafeIp($url) !== null;
    }

    /**
     * Resolve o host uma unica vez e devolve o IP so se ele for publico
     * (nao privado/loopback/reservado). Devolve null pra qualquer coisa
     * invalida ou insegura.
     */
    private static function resolveSafeIp(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return null;
        }

        $ip = gethostbyname($parts['host']);
        $validPublicIp = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return $validPublicIp !== false ? $ip : null;
    }

    /**
     * @return array{path: string, mime: string, size: int}|null
     */
    public static function download(string $url, int $maxBytes): ?array
    {
        // Resolve e valida o IP UMA VEZ aqui, e fixa esse mesmo IP na
        // conexao do curl (CURLOPT_RESOLVE) em vez de deixar o curl
        // re-resolver o hostname sozinho no connect. Sem isso, dava pra
        // burlar a validacao inteira com DNS rebinding: o hostname
        // responde um IP publico na hora do isSafeUrl(), e um IP interno
        // (127.0.0.1, metadado de nuvem etc) alguns milissegundos depois,
        // na hora do curl_exec de verdade — as duas resolucoes eram
        // independentes. Fixar o IP aqui fecha essa janela: o curl usa
        // sempre o endereco que foi validado, nao importa o que o DNS
        // responder depois.
        $ip = self::resolveSafeIp($url);
        $parts = parse_url($url);

        if ($ip === null || empty($parts['host'])) {
            return null;
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 443;

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
            // Forca a conexao pro IP ja validado, mantendo o Host/SNI
            // originais (e assim o certificado TLS do host continua
            // batendo) — e o mecanismo padrao do curl pra isso.
            CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $ip],
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
