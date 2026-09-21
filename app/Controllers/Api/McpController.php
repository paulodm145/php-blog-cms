<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Text;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class McpController
{
    private $categories;
    private $posts;

    public function __construct()
    {
        Auth::requireMcpApiKey();
        $this->categories = new CategoryRepository();
        $this->posts = new PostRepository();
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

        $slug = Text::slugify($name);
        $existing = $slug !== '' ? $this->categories->findBySlug($slug) : null;

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
