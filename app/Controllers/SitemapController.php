<?php

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\CategoryRepository;
use App\Repositories\PageRepository;
use App\Repositories\PostRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\TagRepository;

class SitemapController
{
    public function index(): void
    {
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        $posts = (new PostRepository())->allPublishedForSitemap();
        $pages = (new PageRepository())->allPublishedForSitemap();
        $projects = (new ProjectRepository())->allPublishedForSitemap();
        $categories = (new CategoryRepository())->publishedCounts();
        $tags = (new TagRepository())->publishedCounts();

        $urls = [
            ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => '/blog', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => '/projetos', 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => '/categorias', 'changefreq' => 'weekly', 'priority' => '0.5'],
            ['loc' => '/curriculo', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => '/blog/' . rawurlencode($post['slug']),
                'lastmod' => date('Y-m-d', strtotime($post['updated_at'])),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        foreach ($pages as $page) {
            $urls[] = [
                'loc' => '/' . rawurlencode($page['slug']),
                'lastmod' => date('Y-m-d', strtotime($page['updated_at'])),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        foreach ($projects as $project) {
            $urls[] = [
                'loc' => '/projetos/' . rawurlencode($project['slug']),
                'lastmod' => date('Y-m-d', strtotime($project['updated_at'])),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        foreach ($categories as $category) {
            $urls[] = ['loc' => '/blog/categoria/' . rawurlencode($category['slug']), 'changefreq' => 'weekly', 'priority' => '0.4'];
        }

        foreach ($tags as $tag) {
            $urls[] = ['loc' => '/blog/tag/' . rawurlencode($tag['slug']), 'changefreq' => 'weekly', 'priority' => '0.3'];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($appUrl . $url['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";

            if (isset($url['lastmod'])) {
                echo '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }

            echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            echo '    <priority>' . $url['priority'] . "</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
    }
}
