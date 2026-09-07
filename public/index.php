<?php

declare(strict_types=1);

use App\Controllers\AdminAuthController;
use App\Controllers\AdminCategoryController;
use App\Controllers\AdminCommentController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminMediaController;
use App\Controllers\AdminPageController;
use App\Controllers\AdminPostController;
use App\Controllers\AdminProjectController;
use App\Controllers\AdminResumeCertificationController;
use App\Controllers\AdminResumeController;
use App\Controllers\AdminResumeCourseController;
use App\Controllers\AdminResumeEducationController;
use App\Controllers\AdminResumeExperienceController;
use App\Controllers\AdminSettingController;
use App\Controllers\AdminUpdateController;
use App\Controllers\AdminUserController;
use App\Controllers\BlogController;
use App\Controllers\CommentController;
use App\Controllers\ErrorController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ProjectController;
use App\Controllers\ResumeController;
use App\Controllers\SitemapController;
use App\Core\Auth;
use App\Core\Autoloader;
use App\Core\ErrorPage;
use App\Core\Env;
use App\Core\InstallState;
use App\Core\InstallRoutes;
use App\Core\Router;

$rootPath = dirname(__DIR__);

require_once $rootPath . '/app/Core/Autoloader.php';

$autoloader = new Autoloader($rootPath . '/app');
$autoloader->register();

set_exception_handler(function (\Throwable $exception): void {
    ErrorPage::serverError();
});

// set_exception_handler nao pega Fatal Error "de verdade" (out of memory,
// erro de sintaxe num arquivo carregado depois deste ponto). Sem isso, um
// fatal vira tela em branco quando display_errors esta desligado (padrao
// recomendado em producao) — o shutdown function abaixo garante que pelo
// menos a nossa pagina de erro apareca, e mostra o detalhe se APP_DEBUG=true.
register_shutdown_function(function () use ($rootPath): void {
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if ($error === null || !in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (Env::get('APP_DEBUG', 'false') === 'true') {
        echo '<pre style="white-space:pre-wrap;padding:2rem;font-family:monospace;font-size:.85rem">';
        echo htmlspecialchars($error['message'] . "\nem " . $error['file'] . ':' . $error['line'], ENT_QUOTES, 'UTF-8');
        echo '</pre>';

        return;
    }

    ErrorPage::serverError();
});

Env::load($rootPath . '/.env');

// Precisa iniciar a sessao aqui, bem cedo — antes de qualquer output.
// Auth::check() e chamado dentro de views publicas (ex: post.php, pra
// mostrar o link "Editar post" a admins logados) DEPOIS que a pagina ja
// comecou a ser renderizada. Se a sessao so for iniciada la, session_start()
// falha silenciosamente ("headers already sent", sem aviso visivel com
// display_errors desligado em producao) e o login nunca e reconhecido nas
// paginas publicas, mesmo com o admin autenticado.
Auth::start();

if (!(new InstallState($rootPath))->isInstalled()) {
    InstallRoutes::handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

    return;
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($requestPath === '/install' || strpos($requestPath, '/install/') === 0) {
    ErrorPage::forbidden();

    return;
}

$router = new Router();
$router->get('/', [HomeController::class, 'index']);
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/page/{page}', [BlogController::class, 'page']);
$router->get('/blog/busca', [BlogController::class, 'search']);
$router->get('/blog/categoria/{slug}', [BlogController::class, 'category']);
$router->get('/blog/categoria/{slug}/page/{page}', [BlogController::class, 'categoryPage']);
$router->get('/blog/tag/{slug}', [BlogController::class, 'tag']);
$router->get('/blog/tag/{slug}/page/{page}', [BlogController::class, 'tagPage']);
$router->get('/blog/{year}/{month}', [BlogController::class, 'archive']);
$router->get('/blog/{year}/{month}/page/{page}', [BlogController::class, 'archivePage']);
$router->post('/blog/{slug}/comentarios', [CommentController::class, 'store']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);
$router->get('/categorias', [BlogController::class, 'topics']);
$router->get('/curriculo', [ResumeController::class, 'show']);
$router->get('/curriculo/pdf', [ResumeController::class, 'pdf']);
$router->get('/projetos', [ProjectController::class, 'index']);
$router->get('/projetos/page/{page}', [ProjectController::class, 'page']);
$router->get('/projetos/{slug}', [ProjectController::class, 'show']);
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
// Alvo do ErrorDocument 403 do .htaccess — ver App\Controllers\ErrorController.
$router->get('/erro-acesso-negado', [ErrorController::class, 'forbidden']);

$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/posts', [AdminPostController::class, 'index']);
$router->get('/admin/posts/create', [AdminPostController::class, 'create']);
$router->post('/admin/posts', [AdminPostController::class, 'store']);
$router->get('/admin/posts/{id}/edit', [AdminPostController::class, 'edit']);
$router->post('/admin/posts/{id}/edit', [AdminPostController::class, 'update']);
$router->get('/admin/posts/{id}/status/{status}', [AdminPostController::class, 'status']);
$router->post('/admin/posts/{id}/delete', [AdminPostController::class, 'delete']);
$router->post('/admin/posts/upload-image', [AdminPostController::class, 'uploadImage']);
$router->get('/admin/paginas', [AdminPageController::class, 'index']);
$router->get('/admin/paginas/create', [AdminPageController::class, 'create']);
$router->post('/admin/paginas', [AdminPageController::class, 'store']);
$router->get('/admin/paginas/{id}/edit', [AdminPageController::class, 'edit']);
$router->post('/admin/paginas/{id}/edit', [AdminPageController::class, 'update']);
$router->post('/admin/paginas/{id}/delete', [AdminPageController::class, 'delete']);
$router->get('/admin/projetos', [AdminProjectController::class, 'index']);
$router->get('/admin/projetos/create', [AdminProjectController::class, 'create']);
$router->post('/admin/projetos', [AdminProjectController::class, 'store']);
$router->get('/admin/projetos/{id}/edit', [AdminProjectController::class, 'edit']);
$router->post('/admin/projetos/{id}/edit', [AdminProjectController::class, 'update']);
$router->post('/admin/projetos/{id}/delete', [AdminProjectController::class, 'delete']);
$router->get('/admin/media', [AdminMediaController::class, 'index']);
$router->post('/admin/media/upload', [AdminMediaController::class, 'upload']);
$router->post('/admin/media/{id}', [AdminMediaController::class, 'updateMeta']);
$router->post('/admin/media/{id}/delete', [AdminMediaController::class, 'delete']);
$router->get('/admin/comentarios', [AdminCommentController::class, 'index']);
$router->post('/admin/comentarios/{id}/aprovar', [AdminCommentController::class, 'approve']);
$router->post('/admin/comentarios/{id}/spam', [AdminCommentController::class, 'spam']);
$router->post('/admin/comentarios/{id}/delete', [AdminCommentController::class, 'delete']);
$router->get('/admin/categories', [AdminCategoryController::class, 'index']);
$router->get('/admin/categories/create', [AdminCategoryController::class, 'create']);
$router->post('/admin/categories', [AdminCategoryController::class, 'store']);
$router->get('/admin/categories/{id}/edit', [AdminCategoryController::class, 'edit']);
$router->post('/admin/categories/{id}/edit', [AdminCategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'delete']);
$router->get('/admin/users', [AdminUserController::class, 'index']);
$router->get('/admin/users/create', [AdminUserController::class, 'create']);
$router->post('/admin/users', [AdminUserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [AdminUserController::class, 'edit']);
$router->post('/admin/users/{id}/edit', [AdminUserController::class, 'update']);
$router->post('/admin/users/{id}/delete', [AdminUserController::class, 'delete']);
$router->get('/admin/curriculo', [AdminResumeController::class, 'edit']);
$router->post('/admin/curriculo', [AdminResumeController::class, 'update']);
$router->post('/admin/curriculo/foto', [AdminResumeController::class, 'uploadPhoto']);
$router->get('/admin/curriculo/experiencia/create', [AdminResumeExperienceController::class, 'create']);
$router->post('/admin/curriculo/experiencia', [AdminResumeExperienceController::class, 'store']);
$router->get('/admin/curriculo/experiencia/{id}/edit', [AdminResumeExperienceController::class, 'edit']);
$router->post('/admin/curriculo/experiencia/{id}/edit', [AdminResumeExperienceController::class, 'update']);
$router->post('/admin/curriculo/experiencia/{id}/delete', [AdminResumeExperienceController::class, 'delete']);
$router->post('/admin/curriculo/experiencia/{id}/mover-cima', [AdminResumeExperienceController::class, 'moveUp']);
$router->post('/admin/curriculo/experiencia/{id}/mover-baixo', [AdminResumeExperienceController::class, 'moveDown']);
$router->get('/admin/curriculo/formacao/create', [AdminResumeEducationController::class, 'create']);
$router->post('/admin/curriculo/formacao', [AdminResumeEducationController::class, 'store']);
$router->get('/admin/curriculo/formacao/{id}/edit', [AdminResumeEducationController::class, 'edit']);
$router->post('/admin/curriculo/formacao/{id}/edit', [AdminResumeEducationController::class, 'update']);
$router->post('/admin/curriculo/formacao/{id}/delete', [AdminResumeEducationController::class, 'delete']);
$router->post('/admin/curriculo/formacao/{id}/mover-cima', [AdminResumeEducationController::class, 'moveUp']);
$router->post('/admin/curriculo/formacao/{id}/mover-baixo', [AdminResumeEducationController::class, 'moveDown']);
$router->get('/admin/curriculo/cursos/create', [AdminResumeCourseController::class, 'create']);
$router->post('/admin/curriculo/cursos', [AdminResumeCourseController::class, 'store']);
$router->get('/admin/curriculo/cursos/{id}/edit', [AdminResumeCourseController::class, 'edit']);
$router->post('/admin/curriculo/cursos/{id}/edit', [AdminResumeCourseController::class, 'update']);
$router->post('/admin/curriculo/cursos/{id}/delete', [AdminResumeCourseController::class, 'delete']);
$router->post('/admin/curriculo/cursos/{id}/visibilidade', [AdminResumeCourseController::class, 'visibility']);
$router->get('/admin/curriculo/certificacoes/create', [AdminResumeCertificationController::class, 'create']);
$router->post('/admin/curriculo/certificacoes', [AdminResumeCertificationController::class, 'store']);
$router->get('/admin/curriculo/certificacoes/{id}/edit', [AdminResumeCertificationController::class, 'edit']);
$router->post('/admin/curriculo/certificacoes/{id}/edit', [AdminResumeCertificationController::class, 'update']);
$router->post('/admin/curriculo/certificacoes/{id}/delete', [AdminResumeCertificationController::class, 'delete']);
$router->post('/admin/curriculo/certificacoes/{id}/mover-cima', [AdminResumeCertificationController::class, 'moveUp']);
$router->post('/admin/curriculo/certificacoes/{id}/mover-baixo', [AdminResumeCertificationController::class, 'moveDown']);
$router->get('/admin/settings', [AdminSettingController::class, 'edit']);
$router->post('/admin/settings', [AdminSettingController::class, 'update']);
$router->get('/admin/atualizar', [AdminUpdateController::class, 'show']);
$router->post('/admin/atualizar/run', [AdminUpdateController::class, 'run']);

// Catch-all de páginas (estilo WordPress Pages). Precisa ser a última rota
// GET registrada: só entra em jogo quando nenhuma rota fixa acima bateu.
$router->get('/{slug}', [PageController::class, 'show']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
