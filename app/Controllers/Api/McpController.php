<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Repositories\CategoryRepository;

class McpController
{
    private $categories;

    public function __construct()
    {
        Auth::requireMcpApiKey();
        $this->categories = new CategoryRepository();
    }

    public function categories(): void
    {
        $this->jsonResponse($this->categories->all());
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
