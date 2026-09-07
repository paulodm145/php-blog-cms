<?php

namespace App\Core;

use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Repositories\TagRepository;

/**
 * Monta os dados dos widgets da sidebar (categorias, arquivo por mes, posts
 * recentes, tags), reaproveitados nas paginas publicas (home, listagem,
 * post, categorias).
 */
class Sidebar
{
    private const MONTHS = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    public static function data(): array
    {
        $posts = new PostRepository();

        return [
            'categories' => (new CategoryRepository())->publishedCounts(),
            'tags' => (new TagRepository())->publishedCounts(),
            'archive' => self::groupArchive($posts->archive()),
            'recent' => $posts->paginate(1, 5),
        ];
    }

    private static function groupArchive(array $rows): array
    {
        $years = [];

        foreach ($rows as $row) {
            $year = (int) $row['year'];
            $count = (int) $row['total'];
            $years[$year]['year'] = $year;
            $years[$year]['count'] = ($years[$year]['count'] ?? 0) + $count;
            $years[$year]['months'][] = [
                'year' => $year,
                'month' => (int) $row['month'],
                'label' => self::MONTHS[(int) $row['month']],
                'count' => $count,
            ];
        }

        return array_values($years);
    }
}
