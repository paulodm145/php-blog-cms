<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Env;
use App\Core\ErrorPage;
use App\Core\Html;
use App\Core\Sidebar;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\GalleryRepository;
use App\Repositories\PostRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TagRepository;

class BlogController
{
    private $posts;
    private $categories;
    private $tags;
    private $settings;

    public function __construct()
    {
        $this->posts = new PostRepository();
        $this->categories = new CategoryRepository();
        $this->tags = new TagRepository();
        $this->settings = (new SettingRepository())->all();
    }

    public function index(): void
    {
        $this->renderList(1, null, null);
    }

    public function page(string $page): void
    {
        $this->renderList((int) $page, null, null);
    }

    public function category(string $slug): void
    {
        $this->categoryPage($slug, '1');
    }

    public function categoryPage(string $slug, string $page): void
    {
        $category = $this->categories->findBySlug($slug);

        if ($category === null) {
            ErrorPage::notFound();
            return;
        }

        $this->renderList((int) $page, $category, null);
    }

    public function tag(string $slug): void
    {
        $this->tagPage($slug, '1');
    }

    public function tagPage(string $slug, string $page): void
    {
        $tag = $this->tags->findBySlug($slug);

        if ($tag === null) {
            ErrorPage::notFound();
            return;
        }

        $this->renderList((int) $page, null, $tag);
    }

    public function archive(string $year, string $month): void
    {
        $this->archivePage($year, $month, '1');
    }

    public function archivePage(string $year, string $month, string $page): void
    {
        $this->renderList((int) $page, null, null, (int) $year, (int) $month);
    }

    public function topics(): void
    {
        View::render('site/categories', [
            'title' => 'Categorias | ' . $this->settings['site_name'],
            'description' => $this->settings['blog_description'],
            'settings' => $this->settings,
            'active' => 'categorias',
            'canonical' => $this->canonical('/categorias'),
            'categories' => $this->categories->publishedCounts(),
            'tags' => $this->tags->publishedCounts(),
            'sidebar' => Sidebar::data(),
        ]);
    }

    public function search(): void
    {
        $term = trim((string) ($_GET['q'] ?? ''));
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $totalPosts = $term !== '' ? $this->posts->searchCount($term) : 0;
        $totalPages = max(1, (int) ceil($totalPosts / $perPage));
        $posts = $term !== '' ? $this->posts->search($term, $currentPage, $perPage) : [];

        View::render('site/search', [
            'title' => 'Busca: ' . $term . ' | ' . $this->settings['site_name'],
            'description' => 'Resultados da busca no blog.',
            'settings' => $this->settings,
            'active' => 'busca',
            'canonical' => null,
            'robots' => 'noindex,follow',
            'term' => $term,
            'posts' => $posts,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'sidebar' => Sidebar::data(),
        ]);
    }

    public function show(string $slug): void
    {
        $post = $this->posts->findBySlug($slug);
        $isDraftPreview = false;

        // Rascunho nao aparece pra visitante comum (findBySlug so acha
        // publicado). Se ninguem achou e quem esta olhando e o admin
        // logado, tenta de novo sem o filtro de status — e como o botao
        // "Ver post" do admin consegue mostrar um rascunho sem publicar
        // ele de verdade (skill publicar-conteudo sempre cria como
        // draft; precisa de algum jeito de revisar antes de publicar).
        if ($post === null && Auth::check()) {
            $post = $this->posts->findBySlugForAdmin($slug);
            $isDraftPreview = $post !== null && $post['status'] !== 'published';
        }

        if ($post === null) {
            ErrorPage::notFound();
            return;
        }

        $comments = new CommentRepository();
        $postUrl = $this->canonical('/blog/' . rawurlencode($post['slug']));
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        $hasImage = $post['featured_image'] !== '/assets/images/blog-feature.svg';
        $imageUrl = $appUrl . ($hasImage ? $post['featured_image'] : '/assets/images/og-default.png');
        $publishedIso = $post['published_at'] ? date('c', strtotime($post['published_at'])) : null;

        $content = Html::renderPostContent($post['content']);
        $content = (new GalleryRepository())->expandShortcodes($content, Auth::check());

        View::render('site/post', [
            'title' => $post['title'] . ' | ' . $this->settings['site_name'],
            'description' => $post['excerpt'] ?: $post['title'],
            'settings' => $this->settings,
            'active' => 'blog',
            // Rascunho nunca leva canonical nem indexacao — mesmo que
            // alguem com sessao aberta chegue nele, nao deixa rastro pra
            // buscador nenhum.
            'canonical' => $isDraftPreview ? null : $postUrl,
            'robots' => $isDraftPreview ? 'noindex,nofollow' : null,
            'isDraftPreview' => $isDraftPreview,
            'image' => $post['featured_image'],
            'ogType' => 'article',
            'post' => $post,
            'content' => $content,
            'comments' => $comments->approvedForPost((int) $post['id']),
            'related' => $this->posts->randomExcluding((int) $post['id'], 2),
            'sidebar' => Sidebar::data(),
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Article',
                        'headline' => $post['title'],
                        'description' => $post['excerpt'] ?: $post['title'],
                        'image' => [$imageUrl],
                        'datePublished' => $publishedIso,
                        'dateModified' => $publishedIso,
                        'author' => ['@type' => 'Person', 'name' => $post['author_name'] ?: $this->settings['site_name']],
                        'publisher' => ['@type' => 'Organization', 'name' => $this->settings['site_name']],
                        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
                    ],
                    [
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $appUrl . '/'],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $appUrl . '/blog'],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $postUrl],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private const MONTH_NAMES = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    private function renderList(int $page, ?array $category, ?array $tag, ?int $year = null, ?int $month = null): void
    {
        $currentPage = max(1, $page);
        $perPage = 10;
        $categoryId = $category !== null ? (int) $category['id'] : null;
        $tagId = $tag !== null ? (int) $tag['id'] : null;
        $totalPosts = $this->posts->countPublished($year, $month, $categoryId, $tagId);
        $totalPages = max(1, (int) ceil($totalPosts / $perPage));

        if ($currentPage > $totalPages) {
            ErrorPage::notFound();
            return;
        }

        if ($category !== null) {
            $basePath = '/blog/categoria/' . rawurlencode($category['slug']);
            $kicker = 'Categoria';
            $heading = $category['name'];
        } elseif ($tag !== null) {
            $basePath = '/blog/tag/' . rawurlencode($tag['slug']);
            $kicker = 'Tag';
            $heading = $tag['name'];
        } elseif ($year !== null && $month !== null) {
            $basePath = '/blog/' . $year . '/' . str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $kicker = 'Arquivo';
            $heading = self::MONTH_NAMES[$month] . ' de ' . $year;
        } else {
            $basePath = '/blog';
            $kicker = 'Blog';
            $heading = 'Artigos';
        }

        $path = $currentPage === 1 ? $basePath : $basePath . '/page/' . $currentPage;
        $titleBase = $basePath !== '/blog'
            ? $heading . ' | ' . $this->settings['site_name']
            : 'Blog | ' . $this->settings['site_name'];

        View::render('site/blog', [
            'title' => $currentPage === 1 ? $titleBase : $titleBase . ' - Página ' . $currentPage,
            'description' => $this->settings['blog_description'],
            'settings' => $this->settings,
            'active' => 'blog',
            'canonical' => $this->canonical($path),
            'posts' => $this->posts->paginate($currentPage, $perPage, $year, $month, $categoryId, $tagId),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'basePath' => $basePath,
            'kicker' => $kicker,
            'heading' => $heading,
            'category' => $category,
            'tag' => $tag,
            'sidebar' => Sidebar::data(),
        ]);
    }

    private function canonical(string $path): ?string
    {
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        return $appUrl === '' ? null : $appUrl . $path;
    }
}
