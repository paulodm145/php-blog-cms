<?php

namespace App\Core;

/**
 * Gera uma versao reduzida (largura fixa, altura proporcional) de uma
 * imagem recem-enviada, usando GD — sem dependencia de banco, mesmo
 * espirito estatico de Html/Text. Usada por AdminMediaController logo
 * apos salvar o arquivo original.
 *
 * Nunca lanca excecao: qualquer falha (imagem corrompida, mime nao
 * suportado, imagem ja pequena, imagem grande demais pra memoria
 * disponivel) devolve null, e quem chama cai de volta pro arquivo
 * original — o thumbnail e um extra cosmetico, nunca pode travar o
 * upload em si.
 */
class ImageThumbnail
{
    private const LOADERS = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
        'image/gif' => 'imagecreatefromgif',
    ];

    // Formatos com canal alfa — precisam de imagealphablending/imagesavealpha
    // antes de copiar, senao a transparencia vira preto solido no thumbnail.
    private const ALPHA_MIMES = ['image/png', 'image/webp', 'image/gif'];

    // ~40 megapixels: bem acima de qualquer foto razoavel de blog pessoal,
    // mas ainda uma guarda contra estourar memory_limit=128M ao decodificar
    // um upload absurdamente grande (GD decodifica sem compressao, ~4 bytes
    // por pixel — 40MP cru já são uns 160MB, perto do limite sozinho).
    private const MAX_PIXELS = 40000000;

    public static function generate(string $absolutePath, string $mimeType, int $maxWidth = 400): ?string
    {
        if (!isset(self::LOADERS[$mimeType])) {
            return null;
        }

        $dimensions = @getimagesize($absolutePath);

        if ($dimensions === false || $dimensions[0] <= 0 || $dimensions[1] <= 0) {
            return null;
        }

        [$width, $height] = $dimensions;

        if ($width <= $maxWidth) {
            // Imagem ja e pequena o bastante — nao ha o que reduzir, o
            // original ja serve de thumbnail.
            return null;
        }

        if ($width * $height > self::MAX_PIXELS) {
            return null;
        }

        try {
            $loader = self::LOADERS[$mimeType];
            $source = @$loader($absolutePath);

            if ($source === false) {
                return null;
            }

            $newHeight = (int) round($height * ($maxWidth / $width));
            $newHeight = max(1, $newHeight);

            $thumbnail = imagecreatetruecolor($maxWidth, $newHeight);

            if (in_array($mimeType, self::ALPHA_MIMES, true)) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }

            $copied = imagecopyresampled(
                $thumbnail,
                $source,
                0,
                0,
                0,
                0,
                $maxWidth,
                $newHeight,
                $width,
                $height
            );

            if (!$copied) {
                imagedestroy($source);
                imagedestroy($thumbnail);

                return null;
            }

            $outputPath = self::outputPath($absolutePath);
            $saved = self::save($thumbnail, $outputPath, $mimeType);

            imagedestroy($source);
            imagedestroy($thumbnail);

            return $saved ? $outputPath : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private static function outputPath(string $absolutePath): string
    {
        $directory = pathinfo($absolutePath, PATHINFO_DIRNAME);
        $baseName = pathinfo($absolutePath, PATHINFO_FILENAME);
        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION);

        return $directory . '/' . $baseName . '-thumb.' . $extension;
    }

    private static function save($image, string $outputPath, string $mimeType): bool
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return @imagejpeg($image, $outputPath, 85);
            case 'image/png':
                return @imagepng($image, $outputPath);
            case 'image/webp':
                return @imagewebp($image, $outputPath, 85);
            case 'image/gif':
                return @imagegif($image, $outputPath);
            default:
                return false;
        }
    }
}
