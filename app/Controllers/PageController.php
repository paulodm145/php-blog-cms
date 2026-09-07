<?php

namespace App\Controllers;

use App\Core\Env;
use App\Core\ErrorPage;
use App\Core\Sidebar;
use App\Core\View;
use App\Repositories\PageRepository;
use App\Repositories\SettingRepository;

class PageController
{
    private $pages;
    private $settings;

    public function __construct()
    {
        $this->pages = new PageRepository();
        $this->settings = (new SettingRepository())->all();
    }

    public function show(string $slug): void
    {
        $page = $this->pages->findPublishedBySlug($slug);

        if ($page === null) {
            ErrorPage::notFound();
            return;
        }

        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        View::render('site/page', [
            'title' => $page['title'] . ' | ' . $this->settings['site_name'],
            'description' => $this->settings['blog_description'],
            'settings' => $this->settings,
            'active' => $slug,
            'canonical' => $appUrl === '' ? null : $appUrl . '/' . rawurlencode($slug),
            'page' => $page,
            'sidebar' => Sidebar::data(),
        ]);
    }
}
