<?php

namespace App\Core;

/**
 * Detecta o provedor (YouTube ou Google Drive) de uma URL de video
 * colada no admin e gera o embed/miniatura correspondente. Nunca
 * confia em provider mandado pelo client -- sempre re-detecta aqui a
 * partir da URL crua.
 */
class VideoEmbed
{
    /**
     * @return array{provider: string, external_id: string}|null
     */
    public static function detect(string $url): ?array
    {
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([a-zA-Z0-9_-]{11})#', $url, $matches)) {
            return ['provider' => 'youtube', 'external_id' => $matches[1]];
        }

        if (preg_match('#drive\.google\.com/(?:file/d/|open\?id=)([a-zA-Z0-9_-]+)#', $url, $matches)) {
            return ['provider' => 'google_drive', 'external_id' => $matches[1]];
        }

        return null;
    }

    public static function embedUrl(string $provider, string $externalId): string
    {
        if ($provider === 'youtube') {
            return 'https://www.youtube.com/embed/' . $externalId;
        }

        return 'https://drive.google.com/file/d/' . $externalId . '/preview';
    }

    /**
     * Miniatura automatica -- so o YouTube tem uma confiavel sem
     * autenticacao de API. Google Drive devolve null (quem chama cai
     * pro thumbnail_media_id escolhido manualmente, ou pro placeholder
     * de play na exibicao).
     */
    public static function autoThumbnailUrl(string $provider, string $externalId): ?string
    {
        if ($provider === 'youtube') {
            return 'https://img.youtube.com/vi/' . $externalId . '/hqdefault.jpg';
        }

        return null;
    }
}
