<?php

namespace App\Core;

class Text
{
    private const PROJECT_TYPE_LABELS = [
        'personal' => 'Pessoal',
        'professional' => 'Profissional',
        'freelance' => 'Freelance',
    ];

    /**
     * Rotulo em portugues do tipo de projeto (secao Projetos) — usado no
     * card publico, na pagina de detalhe e na lista do admin.
     */
    public static function projectTypeLabel(?string $type): string
    {
        return self::PROJECT_TYPE_LABELS[$type] ?? '';
    }

    public static function readingTime(string $html): string
    {
        $plain = trim(strip_tags($html));
        $words = $plain === '' ? [] : preg_split('/\s+/u', $plain);
        $minutes = max(1, (int) ceil(count($words) / 200));

        return $minutes . ' min';
    }

    public static function shortDate(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }

        $months = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $timestamp = strtotime($datetime);

        return date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }

    /**
     * Formata um intervalo de datas em dd/mm/aaaa (usado nos cursos do
     * curriculo, que agora guardam data exata, nao so mes/ano). $endDate
     * nulo vira "atual" (curso em andamento); $startDate nulo devolve
     * string vazia (nada cadastrado ainda).
     */
    public static function dateRange(?string $startDate, ?string $endDate): string
    {
        if ($startDate === null || $startDate === '') {
            return '';
        }

        $start = self::numericDate($startDate);

        if ($endDate === null || $endDate === '') {
            return $start . ' — atual';
        }

        $end = self::numericDate($endDate);

        return $start === $end ? $start : $start . ' — ' . $end;
    }

    private static function numericDate(string $date): string
    {
        return date('d/m/Y', strtotime($date));
    }

    /**
     * Formata uma duracao em minutos como "1h29min" / "40h" / "30min".
     * Nulo ou zero devolve string vazia (nada cadastrado).
     */
    public static function duration(?int $totalMinutes): string
    {
        if ($totalMinutes === null || $totalMinutes <= 0) {
            return '';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h' . $minutes . 'min';
        }

        return $hours > 0 ? $hours . 'h' : $minutes . 'min';
    }

    public static function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim($value, '-');
    }

    /**
     * Trunca um texto num limite de caracteres, cortando na ultima palavra
     * inteira e acrescentando "…" — usado na meta description/og:description
     * pra nao passar do que o Google costuma exibir (~155-160 caracteres).
     */
    public static function truncate(string $text, int $length): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " .,;:") . '…';
    }

    public static function formatWhatsapp(string $digits): string
    {
        $digits = preg_replace('/\D/', '', $digits);

        if (strlen($digits) === 13) {
            return '(' . substr($digits, 2, 2) . ') ' . substr($digits, 4, 5) . '-' . substr($digits, 9);
        }

        if (strlen($digits) === 12) {
            return '(' . substr($digits, 2, 2) . ') ' . substr($digits, 4, 4) . '-' . substr($digits, 8);
        }

        return $digits;
    }
}
