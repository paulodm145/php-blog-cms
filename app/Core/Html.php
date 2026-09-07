<?php

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMXPath;

class Html
{
    public static function postContent(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><blockquote><pre><code><h2><h3><h4><img>';

        return strip_tags($html, $allowed);
    }

    /**
     * Icone Font Awesome pra representar um arquivo nao-imagem na
     * biblioteca de midia, escolhido pelo mime type detectado no upload.
     */
    public static function fileIcon(string $mimeType): string
    {
        $map = [
            'pdf' => 'fa-file-pdf',
            'msword' => 'fa-file-word',
            'wordprocessingml' => 'fa-file-word',
            'ms-excel' => 'fa-file-excel',
            'spreadsheetml' => 'fa-file-excel',
            'ms-powerpoint' => 'fa-file-powerpoint',
            'presentationml' => 'fa-file-powerpoint',
            'zip' => 'fa-file-zipper',
            'text/plain' => 'fa-file-lines',
            'text/csv' => 'fa-file-lines',
        ];

        foreach ($map as $needle => $icon) {
            if (strpos($mimeType, $needle) !== false) {
                return $icon;
            }
        }

        return 'fa-file';
    }

    /**
     * Conteudo do post pronto pra exibicao publica: mesma sanitizacao de
     * postContent(), mas cada bloco <pre> vira uma "janela" estilo editor
     * (barra com as 3 bolinhas + botao de copiar) para o highlight.js
     * (carregado em site/post.php) colorir o codigo.
     */
    public static function renderPostContent(string $html): string
    {
        $sanitized = self::postContent($html);

        if (strpos($sanitized, '<pre') === false) {
            return $sanitized;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="html-root">' . $sanitized . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $preNodes = iterator_to_array($xpath->query('//pre'));

        foreach ($preNodes as $pre) {
            /** @var DOMElement $pre */
            self::ensureCodeChild($dom, $pre);
            self::wrapInCodeWindow($dom, $pre);
        }

        $root = $dom->getElementById('html-root');
        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }

    private static function ensureCodeChild(DOMDocument $dom, DOMElement $pre): void
    {
        foreach ($pre->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'code') {
                return;
            }
        }

        $code = $dom->createElement('code');

        while ($pre->firstChild !== null) {
            $code->appendChild($pre->firstChild);
        }

        $pre->appendChild($code);
    }

    private static function wrapInCodeWindow(DOMDocument $dom, DOMElement $pre): void
    {
        $wrapper = $dom->createElement('div');
        $wrapper->setAttribute('class', 'code-window');

        $bar = $dom->createElement('div');
        $bar->setAttribute('class', 'code-window-bar');

        $dots = $dom->createElement('span');
        $dots->setAttribute('class', 'code-window-dots');
        foreach (['red', 'yellow', 'green'] as $color) {
            $dot = $dom->createElement('span');
            $dot->setAttribute('class', 'code-dot code-dot-' . $color);
            $dots->appendChild($dot);
        }
        $bar->appendChild($dots);

        $copyButton = $dom->createElement('button', 'Copiar');
        $copyButton->setAttribute('type', 'button');
        $copyButton->setAttribute('class', 'code-copy-btn');
        $copyButton->setAttribute('aria-label', 'Copiar código');
        $bar->appendChild($copyButton);

        $wrapper->appendChild($bar);

        $pre->parentNode->replaceChild($wrapper, $pre);
        $wrapper->appendChild($pre);
    }
}
