<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Env;
use App\Core\View;
use App\Repositories\ResumeDocumentShareRepository;
use App\Repositories\ResumeExperienceDocumentRepository;

class AdminResumeDocumentShareController
{
    private $shares;
    private $documents;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->shares = new ResumeDocumentShareRepository();
        $this->documents = new ResumeExperienceDocumentRepository();
    }

    public function index(): void
    {
        View::render('admin/partials/resume-document-shares-table', [
            'shares' => $this->shares->listAllForAdmin(),
            'appUrl' => rtrim((string) Env::get('APP_URL', ''), '/'),
        ]);
    }

    public function store(): void
    {
        header('Content-Type: application/json');

        $documentIds = array_map('intval', (array) ($_POST['document_ids'] ?? []));
        $expiresAtInput = (string) ($_POST['expires_at'] ?? '');

        if (empty($documentIds)) {
            http_response_code(422);
            echo json_encode(['error' => 'Selecione ao menos um documento']);
            return;
        }

        $timestamp = strtotime($expiresAtInput);

        if ($timestamp === false || $timestamp <= time()) {
            http_response_code(422);
            echo json_encode(['error' => 'Data de expiração inválida']);
            return;
        }

        $uniqueIds = array_values(array_unique($documentIds));
        $existing = $this->documents->findManyByIds($uniqueIds);

        if (count($existing) !== count($uniqueIds)) {
            http_response_code(422);
            echo json_encode(['error' => 'Um ou mais documentos são inválidos']);
            return;
        }

        $expiresAt = date('Y-m-d H:i:s', $timestamp);
        $share = $this->shares->create($uniqueIds, $expiresAt, Auth::user()['id'] ?? null);
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        echo json_encode([
            'url' => $appUrl . '/compartilhado/' . $share['token'],
            'expires_at' => $expiresAt,
        ]);
    }

    public function revoke(string $id): void
    {
        $this->shares->revoke((int) $id);
        header('Location: /admin/curriculo/documentos');
    }
}
