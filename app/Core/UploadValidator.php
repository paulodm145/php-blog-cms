<?php

namespace App\Core;

class UploadValidator
{
    public const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public const FILE_MIMES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'text/plain' => 'txt',
        'application/csv' => 'csv',
        'text/x-csv' => 'csv',
        'text/csv' => 'csv',
    ];

    public const IMAGE_MAX_SIZE = 5242880;

    public const FILE_MAX_SIZE = 15728640;

    /**
     * @return array{kind:string,extension:string}
     */
    public static function classify(string $mimeType, string $originalName, int $size): array
    {
        if (isset(self::IMAGE_MIMES[$mimeType])) {
            if ($size > self::IMAGE_MAX_SIZE) {
                throw new \RuntimeException('Imagem maior que 5 MB');
            }

            return ['kind' => 'image', 'extension' => self::IMAGE_MIMES[$mimeType]];
        }

        if (isset(self::FILE_MIMES[$mimeType])) {
            if ($size > self::FILE_MAX_SIZE) {
                throw new \RuntimeException('Arquivo maior que 15 MB');
            }

            $extension = self::FILE_MIMES[$mimeType];

            // mime_content_type() nao distingue .txt de .csv — se o arquivo
            // original terminava em .csv, preserva essa extensao.
            if ($mimeType === 'text/plain') {
                $originalExtension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

                if ($originalExtension === 'csv') {
                    $extension = 'csv';
                }
            }

            return ['kind' => 'file', 'extension' => $extension];
        }

        throw new \RuntimeException('Tipo de arquivo não suportado');
    }

    public static function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');

        return $value !== '' ? $value : 'arquivo';
    }

    public static function generateFileName(string $originalName, string $extension): string
    {
        $baseName = self::slugify(pathinfo($originalName, PATHINFO_FILENAME));

        return $baseName . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
    }

    /**
     * $_FILES['files'] chega no formato "invertido" do PHP pra inputs com
     * `multiple` (name/tmp_name/error/size viram arrays paralelos). Aqui
     * viram uma lista de arrays normais, um por arquivo.
     */
    public static function normalizeUploadedFiles($filesField): array
    {
        if (!is_array($filesField) || !isset($filesField['name'])) {
            return [];
        }

        if (!is_array($filesField['name'])) {
            return [$filesField];
        }

        $normalized = [];

        foreach ($filesField['name'] as $index => $name) {
            if (($filesField['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'type' => $filesField['type'][$index] ?? '',
                'tmp_name' => $filesField['tmp_name'][$index] ?? '',
                'error' => $filesField['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $filesField['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }
}
