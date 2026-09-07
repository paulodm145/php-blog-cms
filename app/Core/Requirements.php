<?php

namespace App\Core;

class Requirements
{
    private const MINIMUM_PHP = '7.2.0';

    private $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function check(): array
    {
        return [
            $this->phpVersion(),
            $this->extension('pdo_mysql', true),
            $this->extension('mbstring', true),
            $this->extension('dom', true),
            $this->writable($this->rootPath, 'Raiz gravavel', 'Necessario para gravar o arquivo .env.', true),
            $this->writable($this->rootPath . '/public/uploads', 'Pasta public/uploads gravavel', 'Guarda as imagens de posts (capa e upload no editor).', false),
        ];
    }

    public function passes(): bool
    {
        foreach ($this->check() as $item) {
            if ($item['fatal'] && !$item['ok']) {
                return false;
            }
        }

        return true;
    }

    private function phpVersion(): array
    {
        $ok = version_compare(PHP_VERSION, self::MINIMUM_PHP, '>=');

        return [
            'label' => 'PHP ' . self::MINIMUM_PHP . ' ou superior',
            'ok' => $ok,
            'detail' => 'Versao encontrada: ' . PHP_VERSION,
            'fatal' => true,
        ];
    }

    private function extension(string $name, bool $fatal): array
    {
        $ok = extension_loaded($name);

        return [
            'label' => 'Extensao ' . $name,
            'ok' => $ok,
            'detail' => $ok ? 'Disponivel.' : 'Habilite a extensao ' . $name . ' no painel da hospedagem.',
            'fatal' => $fatal,
        ];
    }

    private function writable(string $path, string $label, string $why, bool $fatal): array
    {
        $ok = is_dir($path) && is_writable($path);
        $detail = $why;

        if (!is_dir($path)) {
            $detail = 'A pasta nao existe. ' . $why;
        } elseif (!$ok) {
            $detail = 'Sem permissao de escrita. Ajuste para 755 no cliente FTP. ' . $why;
        }

        return [
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
            'fatal' => $fatal,
        ];
    }
}
