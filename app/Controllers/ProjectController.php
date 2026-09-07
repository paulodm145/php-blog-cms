<?php

namespace App\Controllers;

use App\Core\Env;
use App\Core\ErrorPage;
use App\Core\Html;
use App\Core\Sidebar;
use App\Core\View;
use App\Repositories\ProjectRepository;
use App\Repositories\SettingRepository;

class ProjectController
{
    private $projects;
    private $settings;

    public function __construct()
    {
        $this->projects = new ProjectRepository();
        $this->settings = (new SettingRepository())->all();
    }

    public function index(): void
    {
        $this->renderList(1);
    }

    public function page(string $page): void
    {
        $this->renderList((int) $page);
    }

    public function show(string $slug): void
    {
        $project = $this->projects->findBySlug($slug);

        if ($project === null) {
            ErrorPage::notFound();
            return;
        }

        $projectUrl = $this->canonical('/projetos/' . rawurlencode($project['slug']));
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        $imageUrl = !empty($project['cover_url']) ? $appUrl . $project['cover_url'] : $appUrl . '/assets/images/og-default.png';
        $modifiedIso = date('c', strtotime($project['updated_at']));

        View::render('site/project', [
            'title' => $project['name'] . ' | ' . $this->settings['site_name'],
            'description' => $project['tagline'] ?: $project['name'],
            'settings' => $this->settings,
            'active' => 'projetos',
            'canonical' => $projectUrl,
            'image' => $project['cover_url'] ?: null,
            'ogType' => 'article',
            'project' => $project,
            'content' => Html::renderPostContent($project['content']),
            'sidebar' => Sidebar::data(),
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'CreativeWork',
                        'name' => $project['name'],
                        'description' => $project['tagline'] ?: $project['name'],
                        'image' => [$imageUrl],
                        'dateModified' => $modifiedIso,
                        'url' => $projectUrl,
                    ],
                    [
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $appUrl . '/'],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Projetos', 'item' => $appUrl . '/projetos'],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => $project['name'], 'item' => $projectUrl],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function renderList(int $page): void
    {
        $currentPage = max(1, $page);
        $perPage = 9;
        $totalProjects = $this->projects->countPublished();
        $totalPages = max(1, (int) ceil($totalProjects / $perPage));

        if ($currentPage > $totalPages) {
            ErrorPage::notFound();
            return;
        }

        $basePath = '/projetos';
        $path = $currentPage === 1 ? $basePath : $basePath . '/page/' . $currentPage;
        $titleBase = 'Projetos | ' . $this->settings['site_name'];

        View::render('site/projects', [
            'title' => $currentPage === 1 ? $titleBase : $titleBase . ' - Página ' . $currentPage,
            'description' => 'Portfólio de projetos pessoais, profissionais e freelance de ' . $this->settings['site_name'] . '.',
            'settings' => $this->settings,
            'active' => 'projetos',
            'canonical' => $this->canonical($path),
            'projects' => $this->projects->paginatePublished($currentPage, $perPage),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'basePath' => $basePath,
            'sidebar' => Sidebar::data(),
        ]);
    }

    private function canonical(string $path): ?string
    {
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        return $appUrl === '' ? null : $appUrl . $path;
    }
}
