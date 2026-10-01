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

        $validation = $this->validateShareInput();

        if ($validation['error'] !== null) {
            http_response_code(422);
            echo json_encode(['error' => $validation['error']]);
            return;
        }

        $share = $this->shares->create($validation['documentIds'], $validation['expiresAt'], Auth::user()['id'] ?? null);
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        echo json_encode([
            'url' => $appUrl . '/compartilhado/' . $share['token'],
            'expires_at' => $validation['expiresAt'],
        ]);
    }

    public function update(string $id): void
    {
        header('Content-Type: application/json');

        $shareId = (int) $id;
        $share = $this->shares->find($shareId);

        if ($share === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Link não encontrado']);
            return;
        }

        if ($share['revoked_at'] !== null) {
            http_response_code(422);
            echo json_encode(['error' => 'Este link já foi revogado']);
            return;
        }

        $validation = $this->validateShareInput();

        if ($validation['error'] !== null) {
            http_response_code(422);
            echo json_encode(['error' => $validation['error']]);
            return;
        }

        $this->shares->update($shareId, $validation['documentIds'], $validation['expiresAt']);
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        echo json_encode([
            'url' => $appUrl . '/compartilhado/' . $share['token'],
            'expires_at' => $validation['expiresAt'],
        ]);
    }

    public function revoke(string $id): void
    {
        $this->shares->revoke((int) $id);
        header('Location: /admin/curriculo/documentos');
    }

    /**
     * @return array{error: ?string, documentIds: int[], expiresAt: string}
     */
    private function validateShareInput(): array
    {
        $documentIds = array_map('intval', (array) ($_POST['document_ids'] ?? []));
        $expiresAtInput = (string) ($_POST['expires_at'] ?? '');

        if (empty($documentIds)) {
            return ['error' => 'Selecione ao menos um documento', 'documentIds' => [], 'expiresAt' => ''];
        }

        $timestamp = strtotime($expiresAtInput);

        if ($timestamp === false || $timestamp <= time()) {
            return ['error' => 'Data de expiração inválida', 'documentIds' => [], 'expiresAt' => ''];
        }

        $uniqueIds = array_values(array_unique($documentIds));
        $existing = $this->documents->findManyByIds($uniqueIds);

        if (count($existing) !== count($uniqueIds)) {
            return ['error' => 'Um ou mais documentos são inválidos', 'documentIds' => [], 'expiresAt' => ''];
        }

        return ['error' => null, 'documentIds' => $uniqueIds, 'expiresAt' => date('Y-m-d H:i:s', $timestamp)];
    }
}
