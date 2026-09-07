<?php

namespace App\Core;

class EnvWriter
{
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function render(array $values): string
    {
        $lines = '';

        foreach ($values as $key => $value) {
            $lines .= $key . '=' . $this->formatValue((string) $value) . "\n";
        }

        return $lines;
    }

    public function write(array $values): bool
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory) || !is_writable($directory)) {
            return false;
        }

        if (is_file($this->filePath) && !is_writable($this->filePath)) {
            return false;
        }

        return file_put_contents($this->filePath, $this->render($values)) !== false;
    }

    public function merge(array $values): bool
    {
        return $this->write(array_merge($this->read(), $values));
    }

    public function read(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $values = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = $this->cleanValue(trim($value));
        }

        return $values;
    }

    private function formatValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s"#\']/', $value) === 1) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        return $value;
    }

    private function cleanValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $first = substr($value, 0, 1);
        $last = substr($value, -1);

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return str_replace(['\\"', '\\\\'], ['"', '\\'], substr($value, 1, -1));
        }

        return $value;
    }
}
