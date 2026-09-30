<?php

namespace App\Controllers;

use App\Core\ErrorPage;
use App\Core\FileDownload;
use App\Core\View;
use App\Repositories\ResumeDocumentShareRepository;
use App\Repositories\ResumeExperienceDocumentRepository;
use App\Repositories\SettingRepository;

class PublicDocumentShareController
{
    private $shares;

    public function __construct()
    {
        $this->shares = new ResumeDocumentShareRepository();
    }

    public function show(string $token): void
    {
        $groups = $this->shares->documentsGroupedForToken($token);
        $settings = (new SettingRepository())->all();

        View::render('site/document-share', [
            'title' => 'Documentos compartilhados | ' . ($settings['site_name'] ?? 'paulorb.dev'),
            'description' => 'Documentos compartilhados via paulorb.dev.',
            'robots' => 'noindex,nofollow',
            'settings' => $settings,
            'groups' => $groups,
            'valid' => !empty($groups),
            'token' => $token,
        ]);
    }

    public function download(string $token, string $id): void
    {
        $share = $this->shares->findValidByToken($token);
        $documentId = (int) $id;

        if ($share === null || !$this->shares->documentBelongsToShare($documentId, (int) $share['id'])) {
            ErrorPage::notFound();
            return;
        }

        $document = (new ResumeExperienceDocumentRepository())->findById($documentId);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . $document['path'];

        if (!is_file($absolutePath)) {
            ErrorPage::notFound();
            return;
        }

        FileDownload::stream($absolutePath, $document['mime_type'], $document['original_name']);
    }
}
