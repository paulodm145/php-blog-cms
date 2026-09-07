<?php

namespace App\Controllers;

use App\Core\Env;
use App\Core\Sidebar;
use App\Core\View;
use App\Repositories\PostRepository;
use App\Repositories\SettingRepository;

class HomeController
{
    private $posts;
    private $settings;

    public function __construct()
    {
        $this->posts = new PostRepository();
        $this->settings = (new SettingRepository())->all();
    }

    public function index(): void
    {
        $latest = $this->posts->paginate(1, 11);
        $featured = array_shift($latest);
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        $tagline = trim(explode('.', $this->settings['blog_description'])[0]);
        $homeTitle = $tagline !== ''
            ? $this->settings['site_name'] . ' — ' . mb_substr($tagline, 0, 60)
            : $this->settings['site_name'];

        View::render('site/home', [
            'title' => $homeTitle,
            'description' => $this->settings['blog_description'],
            'settings' => $this->settings,
            'active' => 'home',
            'canonical' => $appUrl !== '' ? $appUrl . '/' : null,
            'featured' => $featured,
            'posts' => $latest,
            'sidebar' => Sidebar::data(),
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $this->settings['site_name'],
                'description' => $this->settings['blog_description'],
                'url' => $appUrl !== '' ? $appUrl . '/' : null,
            ],
        ]);
    }
}
