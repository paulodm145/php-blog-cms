<?php

namespace App\Core;

class FileDownload
{
    public static function stream(string $absolutePath, string $mimeType, string $originalName): void
    {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $originalName) . '"');
        header('Content-Length: ' . (string) filesize($absolutePath));
        readfile($absolutePath);
    }
}
