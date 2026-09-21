<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Html;
use App\Core\ImageThumbnail;
use App\Core\RemoteImageFetcher;
use App\Repositories\CategoryRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PostRepository;
use App\Repositories\ProjectRepository;

class McpController
{
    private $categories;
    private $posts;
    private $projects;
    private $media;

    public function __construct()
    {
        Auth::requireMcpApiKey();
        $this->categories = new CategoryRepository();
        $this->posts = new PostRepository();
        $this->projects = new ProjectRepository();
        $this->media = new MediaRepository();
    }

    public function categories(): void
    {
        $this->jsonResponse($this->categories->all());
    }

    public function createCategory(): void
    {
        $body = $this->jsonBody();
        $name = trim((string) ($body['name'] ?? ''));

        if ($name === '') {
            $this->jsonResponse(['error' => 'campo "name" e obrigatorio'], 400);
            return;
        }

        $existing = $this->categories->findByName($name);

        if ($existing !== null) {
            $this->jsonResponse($existing, 200);
            return;
        }

        $id = $this->categories->create(['name' => $name, 'slug' => '']);
        $created = $this->categories->find($id);

        $this->jsonResponse($created, 201);
    }

    public function posts(): void
    {
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $search = isset($_GET['q']) ? (string) $_GET['q'] : '';
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $this->jsonResponse($this->posts->listForAdmin($status, $search, $page, 20));
    }

    public function post(string $id): void
    {
        $post = $this->posts->findByIdForAdmin((int) $id);

        if ($post === null) {
            $this->jsonResponse(['error' => 'post nao encontrado'], 404);
            return;
        }

        $this->jsonResponse($post);
    }

    public function createPost(): void
    {
        $body = $this->jsonBody();
        $title = trim((string) ($body['title'] ?? ''));

        if ($title === '') {
            $this->jsonResponse(['error' => 'campo "title" e obrigatorio'], 400);
            return;
        }

        $categoryIds = [];

        foreach ((array) ($body['category_ids'] ?? []) as $categoryId) {
            $categoryIds[] = (int) $categoryId;
        }

        $id = $this->posts->createForAdmin([
            'title' => $title,
            'slug' => '',
            'excerpt' => (string) ($body['excerpt'] ?? ''),
            'content' => Html::postContent((string) ($body['content'] ?? '')),
            'author_id' => 1,
            'featured_image' => (string) ($body['featured_image'] ?? '/assets/images/blog-feature.svg'),
            'published_at' => date('Y-m-d H:i:s'),
            'category_ids' => $categoryIds,
            'tags' => (string) ($body['tags'] ?? ''),
            // Forcado, independente do que veio no body — regra 7 da
            // skill publicar-conteudo, agora validada no servidor.
            'status' => 'draft',
        ]);

        $created = $this->posts->findByIdForAdmin($id);

        $this->jsonResponse($created, 201);
    }

    public function createProject(): void
    {
        $body = $this->jsonBody();
        $name = trim((string) ($body['name'] ?? ''));

        if ($name === '') {
            $this->jsonResponse(['error' => 'campo "name" e obrigatorio'], 400);
            return;
        }

        $id = $this->projects->create([
            'name' => $name,
            'slug' => '',
            'tagline' => (string) ($body['tagline'] ?? ''),
            'content' => Html::postContent((string) ($body['content'] ?? '')),
            'technologies' => (string) ($body['technologies'] ?? ''),
            'cover_media_id' => isset($body['cover_media_id']) ? (int) $body['cover_media_id'] : null,
            'role' => isset($body['role']) ? (string) $body['role'] : null,
            'project_type' => isset($body['project_type']) ? (string) $body['project_type'] : null,
            'live_url' => isset($body['live_url']) ? (string) $body['live_url'] : null,
            'start_date' => isset($body['start_date']) ? (string) $body['start_date'] : null,
            'end_date' => isset($body['end_date']) ? (string) $body['end_date'] : null,
            'featured' => false,
            // Forcado, mesma regra do createPost().
            'status' => 'draft',
        ]);

        $this->jsonResponse(['id' => $id], 201);
    }

    public function attachImageFromUrl(): void
    {
        $body = $this->jsonBody();
        $url = trim((string) ($body['url'] ?? ''));
        $altText = trim((string) ($body['alt_text'] ?? ''));

        if ($url === '' || !RemoteImageFetcher::isSafeUrl($url)) {
            $this->jsonResponse(['error' => 'url invalida ou nao permitida'], 400);
            return;
        }

        $downloaded = RemoteImageFetcher::download($url, 5242880);

        if ($downloaded === null) {
            $this->jsonResponse(['error' => 'nao foi possivel baixar a imagem'], 400);
            return;
        }

        $extension = str_replace('image/', '', $downloaded['mime']);
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $year = date('Y');
        $month = date('m');
        $fileName = 'mcp-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/media/' . $year . '/' . $month;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $finalPath = $uploadDir . '/' . $fileName;
        rename($downloaded['path'], $finalPath);
        // tempnam() cria o arquivo com permissao 600 (so o dono le) — os
        // demais uploads da biblioteca de midia ficam 644/664. Sem isso,
        // a imagem baixada por essa rota podia nao ser servida em
        // producao, dependendo de como o processo web e o PHP-FPM estao
        // mapeados (na hospedagem compartilhada, nem sempre e o mesmo
        // usuario que criou o arquivo).
        chmod($finalPath, 0644);

        $dimensions = @getimagesize($finalPath);
        $width = $dimensions !== false ? $dimensions[0] : null;
        $height = $dimensions !== false ? $dimensions[1] : null;

        $thumbnailAbsolutePath = ImageThumbnail::generate($finalPath, $downloaded['mime']);
        $thumbnailPath = $thumbnailAbsolutePath !== null
            ? '/uploads/media/' . $year . '/' . $month . '/' . basename($thumbnailAbsolutePath)
            : null;

        $mediaId = $this->media->create([
            'file_name' => $fileName,
            'original_name' => $fileName,
            'path' => '/uploads/media/' . $year . '/' . $month . '/' . $fileName,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $downloaded['mime'],
            'kind' => 'image',
            'size' => $downloaded['size'],
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText,
        ]);

        $this->jsonResponse($this->media->findByIdForAdmin($mediaId), 201);
    }

    /**
     * Le o corpo da requisicao como JSON. Nunca lanca excecao pra JSON
     * invalido — devolve array vazio, quem chama decide o que fazer com
     * campos ausentes (mesmo espirito de $_POST ja usado no resto do
     * admin, so que pra corpo JSON em vez de form-encoded).
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function jsonResponse($data, int $status = 200): void
    {
        Auth::logMcpCall($status < 400);
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
